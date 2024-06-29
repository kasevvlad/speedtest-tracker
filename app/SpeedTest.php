<?php

namespace App;

use GuzzleHttp\Client;
use Dotenv\Dotenv;

class SpeedTest
{
    private $discordWebhookUrl;
    private $apiEndpoint;
    private $client;

    public function __construct() {
        $dotenv = Dotenv::createImmutable(dirname(__DIR__).'/');
        $dotenv->load();

        $this->discordWebhookUrl = $_ENV['WEBHOOK_URL'];
        $this->apiEndpoint = $_ENV['API_ENDPOINT'];
        $this->client = new Client();
    }

    public function run()
    {
        while (true) {
            $downloadSpeed = $this->check_speed_download();
            $uploadSpeed = $this->check_speed_upload();
            $pingSpeed = $this->check_speed_ping();
            if ($downloadSpeed !== null && $downloadSpeed < 300) {
                $this->sendDiscordNotification("Attention! download below 300 Mbps. Current download " . $downloadSpeed . " Mbit/s");
            }
            if ($uploadSpeed !== null && $uploadSpeed < 300) {
                $this->sendDiscordNotification("Attention! upload below 300 Mbps. Current upload " . $uploadSpeed . " Mbit/s");
            }
            if ($pingSpeed !== null && $pingSpeed < 20) {
                $this->sendDiscordNotification("Attention! ping below 20. Current ping " . $pingSpeed);
            }
            sleep(3600);
        }
    }

    private function check_speed_download()
    {
        $resp = $this->request();
        return $resp['data']['download'];
    }

    private function check_speed_upload()
    {
        $resp = $this->request();
        return $resp['data']['upload'];
    }

    private function check_speed_ping()
    {
        $resp = $this->request();
        return $resp['data']['ping'];
    }

    private function request()
    {
        $response = $this->client->get("{$this->apiEndpoint}api/speedtest/latest");
        $data = json_decode($response->getBody(), true);
        return $data;
    }

    public function sendDiscordNotification($message) {
        try {
            $response = $this->client->post($this->discordWebhookUrl, [
                'json' => ['content' => $message]
            ]);

            if ($response->getStatusCode() === 204) {
                echo "Success";
            } else {
                echo "Error - " . $response->getStatusCode() . "\n";
            }
        } catch (\Exception $e) {
            echo "Error - " . $e->getMessage() . "\n";
        }
    }
}