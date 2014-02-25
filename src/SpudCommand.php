<?php namespace Isimmons\Spud;

use Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Isimmons\Spud\SpudManager;
use Isimmons\Spud\Phar\Manifest;

class SpudCommand extends SymfonyCommand {

    protected $progress;
    protected $disableUpdgrade;
    protected $customDescription;
    protected static $manifestUri;

    public function __construct($name = 'self-update', $description = null, $disable = false)
    {
        $this->customSetup($name, $description);
        $this->disableUpgrade = $disable;
        parent::__construct($this->customName);
    }

    protected function customSetup($name, $description)
    {
        if($name == '' || is_null($name))
        {
            $this->customName = 'self-update';
        }
        else
        {
            $this->customName = $name;
        }

        if($description == '' || is_null($description))
        {
            $this->customDescription = "Updates the console application to the latest version.";
        }
        else
        {
            $this->customDescription = $description;
        }
    }

    /**
    * Configure command options
    *
    * @return void
    */
    protected function configure()
    {
        $this->setDescription($this->customDescription);
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        
        // setup the progress bar
        $this->progress = $this->getHelperSet()->get('progress');
        $this->progress->setBarCharacter('<info>=</info>');
        $this->progress->setBarWidth(50);

        $this->fire();
    }

    protected function fire()
    {
        /* Check if update capability is disabled.
        * Maybe for known breaking realeases?
        * I don't know why but here it is if the phar
        * maintainer wants to use it.
        */
        if($this->disableUpgrade)
        {
            $this->displayOutput(
                '  The maintainer of this console application has disabled update capability for this version.');
            return;
        }

        // check manifestUri early. Can't work without one.
        if(is_null(static::$manifestUri)) throw new Exception('No manifest URI specified.');

        $this->displayOutput("  <comment>Searching for available updates...</comment>\n");

        $result = $this->update(static::$manifestUri, $this->progress, $this->output);
        
        if($result)
        {
            $this->displayOutput("  <info>{$result}</info>");
        }
        else
        {
            $this->displayOutput("  <error>{$result}</error>");
        }
    }

    protected function update($manifestUri, $progress, $output)
    {
        $app = $this->getApplication();
        $name = $app->getName();
        $version = $app->getVersion();

        $manager = new SpudManager($manifestUri, $progress, $output, $name, $version);
        $result = $manager->doUpdate();
        
        return $result;
    }

     /**
     * Sets the manifest uri.
     *
     * @param  string  $uri
     * @return void
     */
    public function setManifestUri($uri = null)
    {
        static::$manifestUri = $uri;
    }

     /**
     * Display the given output line.
     *
     * @param  string  $output
     * @return void
     */
    protected function displayOutput($output)
    {
        $this->output->write($output);
    }

}
