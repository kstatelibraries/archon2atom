<?php

namespace App\Archon2Atom;

use GuzzleHttp\Client;

class ArchonCollections
{

	protected $client;
	protected $jar;
	protected $archon_session;

    public function __construct()
    {

    	$this->client = new \GuzzleHttp\Client([
    		'cookies' => true,
    		'base_uri' => env('ARCHON_BASE_URL'),
    	]);



    	$this->authenticateArchon();
    }

    public function authenticateArchon() 
    {
    	$auth = $this->client->POST('?p=core/authenticate',[
    		'auth' => [env('ARCHON_USERNAME'), env('ARCHON_PASSWORD'), 'basic'],
    	]);

    	$cookies = $this->client->getConfig('cookies');
    	$cookie_data = $cookies->toArray();
    	$this->archon_session = $cookie_data[0]['Value'];


    }

    public function fetchData()
    {



        $response = $this->client->GET('?p=core/collections&batch_start=1', [
        	'headers' => [
        		'session' => $this->archon_session,
        	]
        ]);

        return (string)$response->getBody();
    }



}
