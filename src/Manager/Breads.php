<?php

namespace Voyager\Admin\Manager;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Voyager\Admin\Classes\Bread as BreadClass;
use Voyager\Admin\Facades\Voyager as VoyagerFacade;

class Breads
{
    protected $formfields;
    protected $path;
    protected $breads;
    protected $backups = [];

    public function __construct()
    {
        $this->path = Str::finish(storage_path('voyager/breads'), '/');
    }

    /**
     * Sets the path where the BREAD-files are stored.
     *
     * @param string $path
     *
     * @return string the current path
     */
    public function setPath($path = null)
    {
        if ($path) {
            $old_path = $this->path;
            $this->path = Str::finish($path, '/');
            if ($old_path !== $path) {
                $this->breads = null;
            }
        }

        return $this->path;
    }

    /**
     * Get all BREADs from storage and validate.
     *
     * @return \Voyager\Admin\Classes\Bread
     */
    public function getBreads()
    {
        if (!$this->breads) {
            VoyagerFacade::ensureDirectoryExists($this->path);
            $this->breads = collect(File::files($this->path))->transform(function ($bread) {
                $content = File::get($bread->getPathName());
                $json = VoyagerFacade::getJson($content);
                if ($json === false) {
                    VoyagerFacade::flashMessage('BREAD-file "'.basename($bread->getPathName()).'" does contain invalid JSON: '.json_last_error_msg(), 'yellow');

                    return;
                }

                $b = new BreadClass($json);

                // Push Exclude backups
                if (Str::contains($bread->getPathName(), '.backup.')) {
                    $date = Str::before(Str::after($bread->getFilename(), '.backup.'), '.json');
                    $this->backups[] = [
                        'table' => $b->table,
                        'path'  => $bread->getFilename(),
                        'date'  => $date,
                    ];

                    return null;
                }

                return $b;
            })->filter(function ($bread) {
                return $bread !== null;
            })->values();
        }

        return $this->breads;
    }

    /**
     * Get backed-up BREADs.
     *
     * @return array
     */
    public function getBackups()
    {
        return $this->backups;
    }

    /**
     * Rollback BREAD to a given file.
     *
     * @param string $path
     *
     * @return bool
     */
    public function rollbackBread($table, $path)
    {
        $path = Str::finish($this->path, '/');
        if ($this->backupBread($table) !== false) {
            return File::delete($path.$table.'.json') && File::copy($path.$path, $path.$table.'.json');
        }

        return false;
    }

    /**
     * Determine if a BREAD exists by the table name.
     *
     * @param string $table
     *
     * @return bool
     */
    public function hasBread($table)
    {
        return $this->getBread($table) !== null;
    }

    /**
     * Get a BREAD by the table name.
     *
     * @param string $table
     *
     * @return \Voyager\Admin\Classes\Bread
     */
    public function getBread($table)
    {
        return $this->getBreads()->where('table', $table)->first();
    }

    /**
     * Get a BREAD by the slug.
     *
     * @param string $slug
     *
     * @return \Voyager\Admin\Classes\Bread
     */
    public function getBreadBySlug($slug)
    {
        return $this->getBreads()->filter(function ($bread) use ($slug) {
            return $bread->slug == $slug;
        })->first();
    }

    /**
     * Get a BREAD by the table.
     *
     * @param string $table
     *
     * @return \Voyager\Admin\Classes\Bread
     */
    public function getBreadByTable($table)
    {
        return $this->getBreads()->filter(function ($bread) use ($table) {
            return $bread->table == $table;
        })->first();
    }

    /**
     * Store a BREAD-file.
     *
     * @param string $bread
     *
     * @return int|bool success
     */
    public function storeBread($bread)
    {
        $this->clearBreads();

        return File::put(Str::finish($this->path, '/').$bread->table.'.json', json_encode($bread, JSON_PRETTY_PRINT));
    }

    /**
     * Clears all BREAD-objects.
     */
    public function clearBreads()
    {
        $this->breads = null;
    }

    /**
     * Delete a BREAD from the filesystem.
     *
     * @param string $table The table of the BREAD
     */
    public function deleteBread($table)
    {
        $ret = File::delete(Str::finish($this->path, '/').$table.'.json');
        $this->clearBreads();

        return $ret;
    }

    /**
     * Backup a BREAD (copy table.json to table.backup.json).
     *
     * @param string $table The table of the BREAD
     */
    public function backupBread($table)
    {
        $old = $this->path.$table.'.json';
        $name = $table.'.backup.'.Carbon::now()->isoFormat('Y-MM-DD@HH-mm-ss').'.json';
        $new = $this->path.$name;

        if (File::exists($old)) {
            if (!File::copy($old, $new)) {
                return false;
            }
        }

        return $name;
    }

    /**
     * Get the search placeholder (Search for Users, Posts, etc...).
     *
     * @param string $placeholder The placeholder
     */
    public function getBreadSearchPlaceholder()
    {
        $breads = $this->getBreads()->shuffle();

        if ($breads->count() > 1) {
            return __('voyager::generic.search_for_breads', [
                'bread'  => $breads[0]->name_plural,
                'bread2' => $breads[1]->name_plural,
            ]);
        } elseif ($breads->count() == 1) {
            return __('voyager::generic.search_for_bread', [
                'bread' => $breads[0]->name_plural,
            ]);
        }

        return __('voyager::generic.search');
    }

    /**
     * Add a formfield.
     *
     * @param string $class The class of the formfield
     */
    public function addFormfield($class)
    {
        if (!$this->formfields) {
            $this->formfields = collect();
        }
        $class = new $class();
        $this->formfields->push($class);
    }

    /**
     * Get formfields.
     *
     * @return Illuminate\Support\Collection The formfields
     */
    public function getFormfields()
    {
        return $this->formfields;
    }

    /**
     * Get a formfield by type.
     *
     * @param string $type The type of the formfield
     *
     * @return object The formfield
     */
    public function getFormfield(string $type)
    {
        if (!$this->formfields) {
            $this->formfields = collect();
        }

        return $this->formfields->filter(function ($formfield) use ($type) {
            return $formfield->type() == $type;
        })->first();
    }

    /**
     * Get the reflection class for a model.
     *
     * @param string $model The fully qualified model name
     *
     * @return ReflectionClass The reflection object
     */
    public function getModelReflectionClass(string $model): \ReflectionClass
    {
        return new \ReflectionClass($model);
    }

    public function getModelScopes(\ReflectionClass $reflection): Collection
    {
        return collect($reflection->getMethods())->filter(function ($method) {
            return Str::startsWith($method->name, 'scope');
        })->whereNotIn('name', ['scopeWithTranslations', 'scopeWithTranslation', 'scopeWhereTranslation'])->transform(function ($method) {
            return lcfirst(Str::replaceFirst('scope', '', $method->name));
        });
    }

    public function getModelComputedProperties(\ReflectionClass $reflection): Collection
    {
        return collect($reflection->getMethods())->filter(function ($method) {
            return Str::startsWith($method->name, 'get') && Str::endsWith($method->name, 'Attribute');
        })->transform(function ($method) {
            $name = Str::replaceFirst('get', '', $method->name);
            $name = Str::replaceLast('Attribute', '', $name);

            return lcfirst($name);
        })->filter();
    }

    public function getModelRelationships(\ReflectionClass $reflection, Model $model, bool $resolve = false): Collection
    {
        $types = [BelongsTo::class, BelongsToMany::class, HasMany::class, HasOne::class];

        return collect($reflection->getMethods())->transform(function ($method) use ($types, $model, $resolve) {
            $type = $method->getReturnType();
            if ($type && in_array(strval($type->getName()), $types)) {
                $columns = [];
                $pivot = [];
                if ($resolve) {
                    $relationship = $model->{$method->getName()}();
                    $table = $relationship->getRelated()->getTable();
                    if ($type->getName() == BelongsToMany::class) {
                        $pivot = array_values(array_diff(VoyagerFacade::getColumns($relationship->getTable()), [
                            $relationship->getForeignPivotKeyName(),
                            $relationship->getRelatedPivotKeyName(),
                        ]));
                    }

                    $columns = VoyagerFacade::getColumns($table);
                }

                return [
                    'method'    => $method->getName(),
                    'type'      => class_basename($type->getName()),
                    'columns'   => $columns,
                    'pivot'     => $pivot,
                    'has_bread' => $this->hasBread($table),
                    'bread'     => $this->getBread($table),
                    'key_name'  => $relationship->getRelated()->getKeyName(),
                    'multiple'  => in_array(strval($type->getName()), [BelongsToMany::class, HasMany::class]),
                ];
            }

            return null;
        })->filter();
    }
}
