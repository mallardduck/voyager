<?php

namespace Voyager\Admin;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Voyager\Admin\Contracts\Plugins\AuthenticationPlugin;
use Voyager\Admin\Contracts\Plugins\AuthorizationPlugin;
use Voyager\Admin\Contracts\Plugins\WidgetPlugin;
use Voyager\Admin\Contracts\Plugins\Features\Filter\Widgets as WidgetFilter;
use Voyager\Admin\Manager\Breads as BreadManager;
use Voyager\Admin\Manager\Plugins as PluginManager;
use Voyager\Admin\Manager\Settings as SettingManager;
use Voyager\Admin\Plugins\AuthenticationPlugin as DefaultAuthPlugin;

class Voyager
{
    /**
     * The route prefix that Voyager will use when registering routes.
     *
     * @var string
     */
    public static $routePath = '/admin';

    protected $messages = [];
    protected $tables = [];
    protected $locales = [];
    protected $breadmanager;
    protected $pluginmanager;
    protected $settingmanager;
    protected $translations = [];

    public function __construct(BreadManager $breadmanager, PluginManager $pluginmanager, SettingManager $settingmanager)
    {
        $this->breadmanager = $breadmanager;
        $this->pluginmanager = $pluginmanager;
        $this->settingmanager = $settingmanager;
    }

    /**
     * Set the callback that should be used to authenticate Horizon users.
     *
     * @param string $pathPrefix
     * @return static
     */
    public static function path(string $pathPrefix = '/admin')
    {
        static::$routePath = $pathPrefix;

        return new static(
            app(BreadManager::class),
            app(PluginManager::class),
            app(SettingManager::class)
        );
    }

    /**
     * Generate a Voyager route URL for Voyager resources and paths.
     *
     * @param       $name
     * @param array $parameters
     * @param bool  $absolute
     *
     * @return string
     */
    public function route($name, $parameters = [], $absolute = true): string
    {
        return route('voyager.' . $name, $parameters, $absolute);
    }

    /**
     * Generate an absolute URL for an asset-file.
     *
     * @param string $path the relative path, e.g. js/voyager.js.
     *
     * @return string
     */
    public function assetUrl($path)
    {
        return route('voyager.voyager_assets').'?path='.urlencode($path);
    }

    /**
     * Flash a message to the UI.
     *
     * @param string $message The message
     * @param string $color   The tailwind color of the exception: blue, yellow, green, red...
     * @param bool   $next    If the message should be flashed after the next request.
     */
    public function flashMessage($message, $color, $timeout = 5000, $next = false)
    {
        $this->messages[] = [
            'message' => $message,
            'color'   => $color,
            'timeout' => $timeout,
        ];
        if ($next) {
            session()->push('voyager-messages', [
                'message' => $message,
                'color'   => $color,
                'timeout' => $timeout,
            ]);
        }
    }

    /**
     * Get all messages.
     *
     * @return array The messages.
     */
    public function getMessages()
    {
        $messages = array_merge($this->messages, session()->get('voyager-messages', []));
        session()->forget('voyager-messages');

        return collect($messages)->unique();
    }

    /**
     * Get all Voyager translation strings.
     *
     * @return array The language strings.
     */
    public function getLocalization()
    {
        return collect(['auth', 'bread', 'builder', 'formfields', 'generic', 'media', 'plugins', 'settings', 'validation'])->flatMap(function ($file) {
            return ['voyager::'.$file => trans('voyager::'.$file)];
        })->merge($this->translations)->toJson();
    }

    /**
     * Add translations to the Voyager namespace.
     *
     * @param string $namespace   The namespace.
     * @param array $translations The translationss.
     */
    public function addTranslations(string $namespace, array $translations)
    {
        $this->translations['voyager::'.$namespace] = $translations;
    }

    /**
     * Get all Routes.
     *
     * @return array The routes.
     */
    public function getRoutes()
    {
        return collect(\Route::getRoutes())->mapWithKeys(function ($route) {
            return [$route->getName() => url($route->uri())];
        })->filter(function ($value, $key) {
            return $key != '';
        });
    }

    /**
     * Get all tables in the database.
     *
     * @return array
     */
    public function getTables()
    {
        return DB::connection()->getDoctrineSchemaManager()->listTableNames();
    }

    /**
     * Get all columns in a given table.
     * 
     * @param string $table The table name.
     * @return array The columns of the table.
     */
    public function getColumns($table)
    {
        if (!array_key_exists($table, $this->tables)) {
            $builder = DB::getSchemaBuilder();
            $this->tables[$table] = $builder->getColumnListing($table);
        }

        return $this->tables[$table];
    }

    /**
     * Get all locales supported by the app.
     *
     * @return array The locales.
     */
    public function getLocales()
    {
        if (count($this->locales) == 0) {
            return config('app.locales', [$this->getLocale()]);
        }

        return $this->locales;
    }

    /**
     * Add a locale to the supported locales.
     *
     * @param string $locale The locale.
     */
    public function addLocale($locale)
    {
        $this->locales[] = $locale;
    }

    /**
     * Set and override all locales.
     *
     * @param array $locales The locales.
     */
    public function setLocales(array $locales)
    {
        $this->locales = $locales;
    }

    /**
     * Get the current app-locale.
     *
     * @return string The current locale.
     */
    public function getLocale()
    {
        return app()->getLocale();
    }

    /**
     * Get the app fallback-locale.
     *
     * @return string The fallback locale.
     */
    public function getFallbackLocale()
    {
        return config('app.fallback_locale', [$this->getLocale()]);
    }

    /**
     * Get if the app is translatable or not.
     *
     * @return bool
     */
    public function isTranslatable()
    {
        return count($this->getLocales()) > 1;
    }

    /**
     * Gets all widgets from installed and enabled plugins filtered by plugins.
     *
     * @return Collection The widgets.
     */
    public function getWidgets()
    {
        $widgets = collect($this->pluginmanager->getAllPlugins()->filter(function ($plugin) {
            return $plugin instanceof WidgetPlugin;
        })->transform(function ($plugin) {
            $width = $plugin->getWidth();
            if ($width >= 1 && $width <= 11) {
                $width = 'w-'.$width.'/12';
            } else {
                $width = 'w-full';
            }

            return (object) [
                'width'         => $width,
                'title'         => $plugin->getTitle(),
                'icon'          => $plugin->getIcon(),
                'component'     => $plugin->getWidgetComponent(),
                'parameters'    => $plugin->getWidgetParameters()
            ];
        }));

        $this->pluginmanager->getAllPlugins()->each(function ($plugin) use (&$widgets) {
            if ($plugin instanceof WidgetFilter) {
                $widgets = $plugin->filterWidgets($widgets);
            }
        });

        return $widgets;
    }

    /**
     * Translate a given string/object/array.
     *
     * @param  mixed  $value The value as a string, object or array.
     * @param  string $locale The locale which should be returned.
     * @param  string $fallback The fallback locale.
     * @return string The translated value.
     */
    public function translate($value, $locale = null, $fallback = null)
    {
        if ($locale == null) {
            $locale = app()->getLocale();
        }
        if ($fallback == null) {
            $fallback = config('app.fallback_locale');
        }

        if (is_string($value)) {
            if (($json = $this->getJson($value)) === false) {
                return $value;
            } else {
                $value = $json;
            }
        }

        if (is_array($value)) {
            return $value[$locale] ?? $value[$fallback] ?? null;
        } elseif (is_object($value)) {
            return $value->{$locale} ?? $value->{$fallback} ?? null;
        }

        return $value;
    }

    /**
     * Set a translation in a given string/object/array.
     *
     * @param  mixed  $input The input as a string, object or array.
     * @param  mixed  $value The value which should be set.
     * @param  string $locale The fallback locale.
     * @return mixed The translated value.
     */
    public function setTranslation($input, $value, $locale = null)
    {
        if ($locale == null) {
            $locale = app()->getLocale();
        }

        if (is_string($input)) {
            $json = $this->getJson($input);
            if ($json === false) {
                $input = [];
            } else {
                $input = $json;
            }
        }

        if (is_array($input)) {
            $input[$locale] = $value;
        } elseif (is_object($input)) {
            $input->{$locale} = $value;
        }

        return $input;
    }

    /**
     * Get a setting, settings in a group or all settings.
     *
     * @param string $key The key of the setting or the group name.
     * @param mixed  $default The value that should be returned when the setting does not exist.
     * @param bool   $translate Should the setting be translated?
     * @return mixed The setting(s).
     */
    public function setting($key = null, $default = null, $translate = true)
    {
        return $this->settingmanager->setting($key, $default, $translate);
    }

    public function getJson($input, $default = false)
    {
        $json = @json_decode($input);
        if (json_last_error() == JSON_ERROR_NONE) {
            return $json;
        }

        return $default;
    }

    /**
     * Set the path where BREAD JSON files are loaded from/stored to.
     *
     * @param string $path The path where BREAD JSON files should be loaded from/stored to.
     */
    public function setBreadPath($path)
    {
        $this->breadmanager->setPath($path);
    }

    /**
     * Set the path where the plugins JSON file is loaded from/stored to.
     *
     * @param string $path The path where the plugins JSON file should be loaded from/stored to.
     */
    public function setPluginsPath($path)
    {
        $this->pluginmanager->setPath($path);
    }

    /**
     * Set the path where the settings JSON file is loaded from/stored to.
     *
     * @param string $path The path where the settings JSON file should be loaded from/stored to.
     */
    public function setSettingsPath($path)
    {
        $this->settingmanager->setPath($path);
    }

    /**
     * Gets the authentication plugin.
     *
     * @return AuthenticationPlugin The AuthenticationPlugin instance.
     */
    public function auth()
    {
        return $this->pluginmanager->getAllPlugins()->filter(function ($plugin) {
            return $plugin instanceof AuthenticationPlugin;
        })->first() ?? new DefaultAuthPlugin();
    }

    /**
     * Ensures that a directory exists.
     *
     * @param string $path The path to the directory.
     */
    public function ensureDirectoryExists($path)
    {
        if (!File::isDirectory($path)) {
            File::makeDirectory($path, 0755, true);
        }
    }

    /**
     * Ensures that a file exists.
     *
     * @param string $path The path to the file.
     * @param string $content The content to write to the file if it doesn't exist.
     */
    public function ensureFileExists($path, $content = '')
    {
        $this->ensureDirectoryExists(dirname($path));
        if (!file_exists($path)) {
            file_put_contents($path, $content);
        }
    }

    /**
     * Authorize an action for a user.
     *
     * @param mixed $user The user.
     * @param mixed $ability The ability.
     * @param array $arguments Additional arguments.
     * @return bool Wether the action is authorized or not.
     */
    public function authorize($user, $ability, $arguments = [])
    {
        $authorized = true;
        $this->pluginmanager->getAllPlugins()->filter(function ($plugin) {
            return $plugin instanceof AuthorizationPlugin;
        })->each(function ($plugin) use ($user, $ability, $arguments, &$authorized) {
            if ($plugin->authorize($user, $ability, $arguments) === false) {
                $authorized = false;
            }
        });

        return $authorized;
    }

    /**
     * Get sanitized thumbnail definitions made in the settings.
     *
     * @return Collection The thumbnail definitions.
     */
    public function getThumbnailDefinitions()
    {
        $thumbs = collect($this->settingmanager->setting('thumbnails'));

        return $thumbs->map(function ($thumb, $name) {
            $name = Str::after($name, 'thumbnails.');
            if (is_object($thumb)) {
                if ($thumb->method == 'fit') {
                    return [
                        'name'      => $name,
                        'method'    => 'fit',
                        'width'     => $thumb->width,
                        'height'    => empty($thumb->height) ? null : $thumb->height,
                        'position'  => empty($thumb->position) ? 'center' : $thumb->position,
                        'upsize'    => empty($thumb->upsize) ? false : $thumb->upsize,
                    ];
                } elseif ($thumb->method == 'crop') {
                    return [
                        'name'      => $name,
                        'method'    => 'crop',
                        'width'     => $thumb->width,
                        'height'    => $thumb->height,
                        'x'         => empty($thumb->x) ? null : $thumb->x,
                        'y'         => empty($thumb->y) ? null : $thumb->y,
                    ];
                } elseif ($thumb->method == 'resize') {
                    return [
                        'name'      => $name,
                        'method'    => 'resize',
                        'width'     => empty($thumb->width) ? null : $thumb->width,
                        'height'    => empty($thumb->height) ? null : $thumb->height,
                        'aspect'    => empty($thumb->keep_aspect_ratio) ? true : $thumb->keep_aspect_ratio,
                        'upsize'    => empty($thumb->upsize) ? false : $thumb->upsize,
                    ];
                }
            }

            return null;
        })->filter(function ($thumb) {
            return $thumb !== null;
        });
    }

    /**
     * @param string $breadName
     *
     * @return Classes\Bread
     */
    public function getBreadByName($breadName)
    {
        return $this->breadmanager->getBreadByName($breadName);
    }
}
