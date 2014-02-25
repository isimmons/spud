<?php namespace Isimmons\Spud;

use Isimmons\Spud\Phar\Manifest;
use Isimmons\Spud\Phar\Updater;
use Isimmons\Spud\Exceptions\BadManifestUriException;
use Isimmons\Spud\Exceptions\BadPharFileException;
use Isimmons\Spud\Exceptions\MissingMetaDataException;

class SpudManager {

    protected $manifestUri;
    protected $progress;
    protected $output;
    protected $name;
    protected $currentVersion;
    protected $runningFile;
    protected $sha1;
    protected $availableUpdates;
    

    public function __construct($manifestUri, $progress, $output, $name, $version)
    {
        $this->manifestUri = $manifestUri;
        $this->progress = $progress;
        $this->output = $output;
        $this->name = $name;
        $this->currentVersion = $version;
    }

    public function doUpdate()
    {
        $this->progress->start($this->output, 100);
        $this->updateProgress(12);
        
        $this->checkAppMetaData();
        $this->checkUri();
        $this->updateProgress(13);

        $this->setRunningFile();
        $this->setSha1();
        $this->updateProgress(12);

        $this->availableUpdates = $this->getAvailableUpdates();
        $this->updateProgress(13);

        //now choose the latest update,
        $update = $this->findLatestUpdate();        
        $this->updateProgress(12);

        if($update === false)
        {
            $this->progress->finish();

            return "Already up to date! You are running {$this->name} v{$this->currentVersion}";
        }

        //if new update send the update off to the updater
        $updater = new Updater($update, $this->runningFile);
        $this->updateProgress(13);
        $result = $updater->doUpdate();

        $this->updateProgress(25); //The home stretch
        $this->progress->finish();

        if($result)
        {
            return "Successfully updated to v{$updater->getNewVersion()}";
        }

        return "Unable to update. Please submit an issue to the issue tracker @ https://github.com/isimmons/spud/issues";

    }

    protected function checkUri()
    {
        //first validate with a really inclusive regex. Inclusive because we can't assume it's a github url
        if( ! preg_match('/[-a-zA-Z0-9@:%_\+.~#?&\/\/=]{2,256}\.[a-z]{2,4}\b(\/[-a-zA-Z0-9@:%_\+.~#?&\/\/=]*)?/si', $this->manifestUri))
            throw new BadManifestUriException('The manifest URI is not a valid URI.');

        //Now see if the uri connects
        if( ! file_get_contents($this->manifestUri))
            throw new BadManifestUriException('The manifest URI did not return any contents.');
    }

    protected function checkAppMetaData()
    {
        if($this->name == '' || is_null($this->name))
            throw new MissingMetaDataException('Please provide the correct name for this app.');

        if($this->currentVersion == '' || is_null($this->currentVersion))
            throw new MissingMetaDataException('Please provide the correct current version for this app.');

        return true;
    }


    protected function getAvailableUpdates()
    {
        $manifest = new Manifest($this->manifestUri);

        $manifest->retreiveManifest();

        return $manifest->getUpdates();
    }

    protected function findLatestUpdate()
    {
        // get location of current runningFile in updates array by it's sha1
        $current = null;
        foreach($this->availableUpdates as $index => $update)
        {
            if($this->sha1 == $update['sha1'])
                $current = $update;
        }

        //find latest from availableUpdates and compare to make sure it's not the same one
        $latest = end($this->availableUpdates);

        if($latest['sha1'] != $current['sha1'])
            return $latest;

        return false;
    }

    protected function setRunningFile()
    {
        $file = realpath($_SERVER['argv'][0]);

        if(! is_file($file))
            throw new BadPharFileException('Unable to set the current phar file for update.');

        $this->runningFile = $file;
    }

    protected function setSha1()
    {
        if($sha1 = sha1_file($this->runningFile))
        {
            $this->sha1 = $sha1;
        }
        else
        {
            throw new BadPharFileException('Unable to get sha1 of current phar file.');
        }
    }

    /**
    * Advances the progress bar incrementally so it looks more real
    *
    * @param integer $steps
    * @return void
    */
    protected function updateProgress($steps = 0)
    {
        for($i = 0; $i < $steps; $i++)
        {
            usleep(10000);
            $this->progress->advance();
        }
    }



}