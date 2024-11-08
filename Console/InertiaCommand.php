<?php 
namespace WPINT\Inertia\Console;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;
use WPINT\Framework\Console\Command;
use WPINT\Framework\Console\SubCommandAttribute;
use Wpint\Support\Facades\CLI;

class InertiaCommand extends Command
{

    /**
     * Commane name
     *
     * @var string
     */
    public string $command = 'inertia';

    /**
     * is typescript
     *
     * @var boolean
     */
    protected $typescript = false;

    /**
     * migrate up
     *
     * @return void
     */
    #[SubCommandAttribute('Install inertia:react in your plugin')]
    protected function install()
    {
        if(env('APP_ENV') === 'production') CLI::error('You are in the production mode.');
        
        CLI::log("Installing Inertia ...");
        if(getcwd() !== WPINT_PLUGIN_PATH) CLI::error("You're not in the  framework-plugin directory");
        $shell_command = "npm install  --production=false --loglevel verbose " . $this->getNpmPackages() ;
        $shell_command .= " && npm install -D --production=false --loglevel verbose " . $this->getNpmDevPackages();
        $shell_command .= " && npm update --production=false --loglevel verbose ";
        // // install  dependencies
        shell_exec( $shell_command );
        
        // copy src DIR & FIlES
        CLI::confirm('This command will replaces [components, css, Pages] directories and [app[.jsx,.tsx] file in the src directory, continue? ');
        
        try{
            $configDir = dirname(__DIR__, 1) . '/Config';
            $configDir .= $this->typescript ? '/Typescript' : '/Common';  
            // copy src files
            $srcDest = WPINT_PLUGIN_PATH . '/resources/scripts/src';
            File::copyDirectory($configDir . '/src', $srcDest);
            // copy config files
            $files = File::files($configDir);
            foreach( $files as $file)
            {
                if(method_exists($file, 'getPathname'))
                    File::copy($file->getPathname(), WPINT_PLUGIN_PATH . '/' . $file->getFilename());
            }
    
            CLI::success("Inertia installed successfuly.");
        }catch(Throwable $th)
        {
            CLI::error($th->getMessage());
        }
    }

    /**
     * Get Composer Packages
     *
     * @return string
     */
    private function getComposerPackages() : string
    {
        return ' wpint/inertia ';
    }
    
    /**
     * Get NPM Packages
     *
     * @return string
     */
    private function getNpmPackages() : string
    {
        return "  react react-dom react-router-dom @inertiajs/inertia @inertiajs/react  ";
    }

    /**
     * Get NPM DEV Packages
     *
     * @return string
     */
    private function getNpmDevPackages() : string
    {
        $base = " @vitejs/plugin-react  autoprefixer tailwindcss postcss ";
        if( ! $this->typescript)   return $base;
        
        $base .= " @types/node @types/react @types/react-dom ";
        return $base;
    }
}       
