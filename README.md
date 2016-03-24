# CMS
Content Management System built on Twig and Laravel

# Usage
## Service provider & Facade
Add the following lines in the `app.php` config file:

Providers:
    
    CMS\ServiceProvider::class,
    

Aliases:
    
    'Twig'      => TwigBridge\Facade\Twig::class,
    'YAML'      => Symfony\Component\Yaml\Yaml::class,
     
    'CMS'       => CMS\Facade\CMS::class,
    'CMS_Helper'=> CMS\Facade\Scaffolding::class,
    'CMS_Parser'=> CMS\Facade\Parser::class,

## Routing
Add at the end of the `routes.php` file

    /*
     *  Catch-all route for views
     */
    Route::get('{route}', function ($route) {
        return CMS::view($route);
    })->where('route', '.*');
    
Add a twig file under `resources/views/pages` to serve the view without a controller in between.

## Within controllers
CMS views can be served through the CMS facade using `CMS::view('page')`. Note that paths are relative to the pages directory.

# Folder configuration
Place the following directories directly under the template root (default `resources/views`)

* layout
* menus
* pages
* partials

# Extended Twig syntax
### {% page %}
Used in layout files for indicating where the page is placed in the layout.

### {% title %}
Placeholder tag for the page title.

### Include helpers
The following helper tags are included to clean up the syntax. All paths are relative to the include type.

* {% menu 'filename' %}
* {% partial 'filename' %}

# Layout pages
The pagecontains an optional config section in the beginning of the file. The divider is `===`.

Typical layout file:
    
    title: Welcome
    layout: default
    with: 
    - world: "Twig"
    data:
    - users:all
    
    ===
    
    Hello {{ world }}
    
    <br />
    
    {% for user in users %}
       <p>{{ user.name }}</p>
    {% endfor %}
    

## Available config tags
* `title`: Page title, appended using {% title %}
* `layout`: Name of a file in the layout directory, with or without the .twig extension
* `with`: Key value attributes to be sent to the page
* `data`: Data provider accessor

# Data Providers
Data providers extend the `CMS\DataProvider` class and can be used to serve data without a controller.

Use `php artisan make:data ProviderName` to create a new data provider. By default data providers are located in the `app/CMS/Data` directory.

Data providers must implement three methods:
* `getAccessor`: name of the accessor called by the config, e.g. 'users'
* `dataAll()`: returns all entries, called with `<accessor>:all`
* `dataOne($id)`: returns a single entry, called with `<accessor>:one[1]`

Additional methods can be created by additional methods. 

## Accessing the data
The data is automatically sent to the view and can be accessed by the accessor name, e.g. `{{ users }}`. If `users:one[1]` was called, the accessor is in singular, i.e. `user`.

## Pseudo numbers
Accessor methods accept the following pseudo numbers (example: users:one[#auth]):

* `#auth`: returns the id of the authenticated user, or null if no login exists
* `#url`: returns the last url query, e.g. `http://example.com/foo/bar` returns `bar`

The pseudo numbers are defined in `CMS\Parser\DataParser.php`.
