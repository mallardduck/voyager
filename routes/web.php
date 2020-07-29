<?php

Route::group(['as' => 'voyager.'], function () {
    $namespace = '\\Voyager\\Admin\\Http\\Controllers\\';

    Route::group(['middleware' => 'voyager.admin'], function () use ($namespace) {
        Route::view('/', 'voyager::dashboard')->name('dashboard');
        Route::post('globalsearch', ['uses' => $namespace.'VoyagerController@globalSearch', 'as' => 'globalsearch']);

        // BREAD builder
        Route::group([
            'as'     => 'bread.',
            'prefix' => 'bread',
        ], function () use ($namespace) {
            Route::get('/', ['uses' => $namespace.'BreadBuilderController@index', 'as' => 'index']);
            Route::get('create/{table}', ['uses' => $namespace.'BreadBuilderController@create', 'as' => 'create']);
            Route::get('edit/{table}', ['uses' => $namespace.'BreadBuilderController@edit', 'as' => 'edit']);
            Route::put('{table}', ['uses' => $namespace.'BreadBuilderController@update', 'as' => 'update']);
            Route::post('get-properties', ['uses' => $namespace.'BreadBuilderController@getProperties', 'as' => 'get-properties']);
            Route::post('get-breads', ['uses' => $namespace.'BreadBuilderController@getBreads', 'as' => 'get-breads']);
            Route::post('backup-bread', ['uses' => $namespace.'BreadBuilderController@backupBread', 'as' => 'backup-bread']);
            Route::post('rolback-bread', ['uses' => $namespace.'BreadBuilderController@rollbackBread', 'as' => 'rollback-bread']);
            Route::delete('{table}', ['uses' => $namespace.'BreadBuilderController@destroy', 'as' => 'delete']);
        });
        // BREADs
        foreach (resolve(\Voyager\Admin\Manager\Breads::class)->getBreads() as $bread) {
            $controller = $namespace.'BreadController';
            if (!empty($bread->controller)) {
                $controller = \Illuminate\Support\Str::start($bread->controller, '\\');
            }
            Route::group([
                'as'     => $bread->slug.'.',
                'prefix' => $bread->slug,
            ], function () use ($bread, $controller) {
                // Browse
                Route::view('/', 'voyager::bread.browse', compact('bread'))->name('browse');
                Route::post('/data', ['uses'=> $controller.'@data', 'as' => 'data', 'bread' => $bread]);

                // Edit
                Route::get('/edit/{id}', ['uses' => $controller.'@edit', 'as' => 'edit', 'bread' => $bread]);
                Route::put('/{id}', ['uses' => $controller.'@update', 'as' => 'update', 'bread' => $bread]);

                // Add
                Route::get('/add', ['uses' => $controller.'@add', 'as' => 'add', 'bread' => $bread]);
                Route::post('/', ['uses' => $controller.'@store', 'as' => 'store', 'bread' => $bread]);

                // Delete
                Route::delete('/', ['uses' => $controller.'@delete', 'as' => 'delete', 'bread' => $bread]);
                Route::patch('/', ['uses' => $controller.'@restore', 'as' => 'restore', 'bread' => $bread]);

                // Read
                Route::get('/{id}', ['uses' => $controller.'@read', 'as' => 'read', 'bread' => $bread]);

                // Relationship data
                Route::post('/relationship', ['uses' => $controller.'@relationship', 'as' => 'relationship', 'bread' => $bread]);
            });
        }

        // UI Routes
        Route::view('ui', 'voyager::ui.index')->name('ui');

        // Settings
        Route::get('settings', ['uses' => $namespace.'SettingsController@index', 'as' => 'settings.index']);
        Route::post('settings', ['uses' => $namespace.'SettingsController@store', 'as' => 'settings.store']);

        // Plugins
        Route::get('plugins', function () {
            return view('voyager::plugins.browse');
        })->name('plugins.index');
        Route::post('plugins/enable', ['uses' => $namespace.'PluginsController@enable', 'as' => 'plugins.enable']);
        Route::post('plugins', ['uses' => $namespace.'PluginsController@get', 'as' => 'plugins.get']);
        Route::get('plugins/settings/{key}', ['uses' => $namespace.'PluginsController@settings', 'as' => 'plugins.settings']);

        // Logout
        Route::get('logout', ['uses' => $namespace.'AuthController@logout', 'as' => 'logout']);

        // Media
        Route::get('media', ['uses' => $namespace.'MediaController@index', 'as' => 'media']);
        Route::post('upload', ['uses' => $namespace.'MediaController@uploadFile', 'as' => 'media.upload']);
        Route::post('download', ['uses' => $namespace.'MediaController@download', 'as' => 'media.download']);
        Route::post('list', ['uses' => $namespace.'MediaController@listFiles', 'as' => 'media.list']);
        Route::delete('delete', ['uses' => $namespace.'MediaController@delete', 'as' => 'media.delete']);
        Route::post('create_folder', ['uses' => $namespace.'MediaController@createFolder', 'as' => 'media.create_folder']);

        //
        Route::post('get-disks', ['uses' => $namespace.'VoyagerController@getDisks', 'as' => 'get-disks']);
        Route::post('get-thumbnail-options', ['uses' => $namespace.'VoyagerController@getThumbnailOptions', 'as' => 'get-thumbnail-options']);
    });

    // Login
    Route::get('login', ['uses' => $namespace.'AuthController@login', 'as' => 'login']);
    Route::post('login', ['uses' => $namespace.'AuthController@processLogin', 'as' => 'login']);
    Route::post('forgot-password', ['uses' => $namespace.'AuthController@forgotPassword', 'as' => 'forgot_password']);

    // Asset routes
    Route::get('voyager-assets', ['uses' => $namespace.'VoyagerController@assets', 'as' => 'voyager_assets']);
});
