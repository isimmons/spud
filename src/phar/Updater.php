<?php namespace Isimmons\Spud\Phar;

use Phar;
use SplFileObject;
use UnexpectedValueException;
use Isimmons\Spud\Exceptions\FileCleanupException;
use Isimmons\Spud\Exceptions\FailedDownloadException;
use Isimmons\Spud\Exceptions\FailedCopyException;
use Isimmons\Spud\Exceptions\FilePermissionsException;

class Updater {

    protected $update;
    protected $runningFile;
    protected $newFile;

    public function __construct($update, $runningFile)
    {
        $this->update = $update;

        $this->runningFile = $runningFile;
    }

    public function getNewVersion()
    {
        return '0.0.2';
    }

    public function doUpdate()
    {
        $this->getNewFile();

        if(is_null($this->newFile))
            throw new FailedDownloadException('The new update file did not download.');

        $mode = 0755;

        if (file_exists($this->runningFile))
        {
            $mode = fileperms($this->runningFile) & 511;
        }

        if( ! copy($this->newFile, $this->runningFile))
        {
            throw new FailedCopyException('Failed to copy new update file.');
        }

        if( ! chmod($this->runningFile, $mode))
        {
            throw new FilePermissionsException('Failed to set file permissions on the new file.');
        }

        $this->deleteTempFile();

        return true;
    }

    protected function getNewFile()
    {
        if(is_null($this->newFile))
        {
            unlink($this->newFile = tempnam(sys_get_temp_dir(), 'spu'));
            mkdir($this->newFile);

            $this->newFile .= DIRECTORY_SEPARATOR . $this->update['name'];

            $in = new SplFileObject($this->update['url'], 'rb', false);
            $out = new SplFileObject($this->newFile, 'wb', false);

            while(! $in->eof())
            {
                $out->fwrite($in->fgets());
            }

            unset($in, $out);

            if($this->update['sha1'] !== sha1_file($this->newFile))
            {
                $this->deleteTempFile();

                throw new BadChecksumException('SHA1 checksum mismatch.');
            }

            try
            {
                new Phar($this->newFile);
            }
            catch(UnexpectedValueException $e)
            {
                $this->deleteTempFile();
                throw $e;
            }
        }
    }

    protected function deleteTempFile()
    {
        if($this->newFile)
        {
            if(file_exists($this->newFile))
            {
                if(! unlink($this->newFile))
                    throw new FileCleanupException('Unable to delete temp files.');
            }

            $dir = dirname($this->newFile);

            if(is_dir($dir))
            {
                if(! rmdir($dir))
                    throw new FileCleanupException('Unable to delete temp files.');
            }

            $this->newFile = null;
        }
    }



}