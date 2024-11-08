<?php 
namespace WPINT\Inertia;

use Illuminate\Contracts\Support\Arrayable;
use Inertia\Inertia;
use Wpint\Support\Facades\WPAPI;
use Wpint\WPAPI\Hook\Enum\HookTypeEnum;
use Illuminate\Http\Request;
use Inertia\ResponseFactory;
use WPINT\Framework\Foundation\Vite;
use WPINT\Framework\ServiceProvider;
use WPINT\Inertia\Console\InertiaCommand;
use Wpint\Support\Facades\WPFile;

class InertiaServiceProvider extends ServiceProvider
{

    /**
     * Providers commands
     *
     * @var array
     */
    public static $commands = [
        InertiaCommand::class
    ];

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register() : void
    {
        // Register your service
        $this->app->singleton(ResponseFactory::class);
        $this->app->extend(ResponseFactory::class, function ($factory, $app) {
            
            return new class extends ResponseFactory {
                public function render($component, $props = [])
                {
                    if ($props instanceof Arrayable) {
                        $props = $props->toArray();
                    }
                    
                    $headers = array_change_key_case(getallheaders(), CASE_LOWER);
                    $page = [
                        'component' => $component,
                        'props'     => array_merge($this->sharedProps, $props),
                        'version'   =>  $this->getVersion(),
                        'url'   =>  request()->getRequestUri()
                    ];
                    if(
                        isset($headers['x-requested-with'])
                        && $headers['x-requested-with'] === 'XMLHttpRequest'
                        && isset($headers['x-inertia'])
                        && $headers['x-inertia'] === 'true'
                    ){
                        
                        return wp_send_json($page, 200);
                    }
                    http_response_code(200);
                    echo view(Vite::rootView(), ['page' => $page]);
                    return;
                }
            };
        });


    }

    /**
     * Bootstrap any application service
     *
     * @return void
     */
    public function boot(): void
    {

        $this->registerRequestMacro();
        
        /**
         * Inertia config
         */
        WPAPI::hook()
        ->name('init')
        ->type(HookTypeEnum::ACTION)
        ->callback(function()
        {
            header('Vary: Accept');
            header('X-Inertia: true');
            $plugin_template = dirname(plugin_dir_path(__FILE__), 2) . '/resources/views/'.Vite::rootView().'.blade.php';
            Inertia::setRootView($plugin_template);
                // Multiple values
                Inertia::share([
                    // Synchronously
                    'site' => [
                        'name'          =>  get_bloginfo('name'),
                        'description'   =>  get_bloginfo('description'),
                        'public_url'    =>  plugin_dir_url(dirname(__FILE__, 2)) . 'public'
                    ],
                    // Lazily
                    'auth' => function () {
                        if (is_user_logged_in()) {
                            return [
                                'user' => wp_get_current_user()
                            ];
                        }
                    },          
                ]);

                // If you're using Laravel Mix, you can
                // use the mix-manifest.json for this.
                $manifest = WPINT_PLUGIN_PATH . '/resources/scripts/dist/.vite/manifest.json'; 
                if(WPFile::exists($manifest))
                {
                    $version = md5_file($manifest);
                    Inertia::version($version);
                }
        })
        ->register();

    }

    /**
     * Register Route Macto
     *
     * @return void
     */
    protected function registerRequestMacro()
    {
        Request::macro('inertia', function () {
            return boolval($this->header('X-Inertia'));
        });
    }

    

    
}