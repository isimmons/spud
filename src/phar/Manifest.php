<?php namespace Isimmons\Spud\Phar;

use Herrera\Json\Json;
use Herrera\Version\Comparator;
use Herrera\Version\Parser;
use Herrera\Version\Version;

class Manifest {

    protected $manifestUri;
    protected $manifest;
    protected $updates;
    protected $json;
    protected $schema;

    public function __construct($manifestUri)
    {
        $this->manifestUri = $manifestUri;

        $this->json = new Json;

        $this->schema = __DIR__.'/schema.json';
    }

    public function retreiveManifest()
    {
        $decoded = $this->decodeManifest();
        
        if($this->validate($decoded))
            $this->updates = $this->findUpdates($decoded);
    }

    protected function decodeManifest()
    {
        return $this->json->decodeFile($this->manifestUri);
    }

    protected function validate($decoded)
    {
        $this->json->validate($this->json->decodeFile($this->schema), $decoded);

        return true;
    }

    public function getUpdates()
    {
        return $this->updates;
    }

    protected function findUpdates($decoded)
    {
        $updates = [];

        foreach($decoded as $update)
        {
            $version = Parser::toVersion($update->version);
            $publicKey = isset($update->publicKey) ? $update->publicKey : null;

            $updates[] = [
                'name' => $update->name,
                'sha1' => $update->sha1,
                'url' => $update->url,
                'version' => $version,
                'publicKey' => $publicKey
            ];
        }

        usort($updates, function($a, $b)
        {
            return Comparator::isGreaterThan(
                $a['version'],
                $b['version']
            );
        });

        return $updates;
    }

}