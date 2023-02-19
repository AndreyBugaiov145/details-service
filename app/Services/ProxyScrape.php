<?php

namespace App\Services;

use GuzzleHttp\Client;

/**
 * Proxyscrape.com API Handler Endpoint
 */
final class ProxyScrape  {

   private $options;
   private $client;
   private $defaults;

   const V2_BASE_URL = "https://api.proxyscrape.com/v2/";

   public function __construct(array $options) {
      $this->options = $this->parse_options($options);
      $this->client = new Client(["base_uri" => self::V2_BASE_URL]);
   }

   public function get(): array {
      $body = $this->request();

      return $body;
   }

   public function request(): array {
      $url = $this->generate_request_url($this->options);
      $response = $this->client->request("GET", "?" . $url);
      $body = (string) $response->getBody();

      return explode(PHP_EOL, $body);
   }

   private function generate_request_url($options): string {
      return http_build_query($options);
   }

   private function get_defaults(): array {
      $defaults = [];
      $defaults["timeout"] = 1000;
      $defaults["protocol"] = "http";
      $defaults["country"] = "all";
      $defaults["ssl"] = "all";
      $defaults["anonymity"] = "all";

      return $defaults;
   }

   private function parse_options(array $options): array {
      $defaults = $this->get_defaults();

      $options["request"] = "displayproxies";

      if(!isset($options["timeout"]) || empty($options["timeout"])) {
         $options["timeout"] = $defaults["timeout"];
      }

      if(!isset($options["protocol"]) || empty($options["protocol"])) {
         $options["protocol"] = $defaults["protocol"];
      }

      if(!isset($options["country"]) || empty($options["country"])) {
         $options["country"] = $defaults["country"];
      }

      if(!isset($options["ssl"]) || empty($options["ssl"])) {
         $options["ssl"] = $defaults["ssl"];
      }

      if(!isset($options["anonymity"]) || empty($options["anonymity"])) {
         $options["anonymity"] = $defaults["anonymity"];
      }

      return $options;
   }
}
