<?php
use GuzzleHttp\Client;

class Github {

    public static $BASE_URL = 'https://api.github.com';


    static function search_repo($query, $language) {
        $client = new Client(['base_uri' => Github::$BASE_URL]);
        $response = $client->get("search/repositories?q=$query+language:$language");
        echo $query;
        if($response->getStatusCode() != 200) {
            return ["error" => "Something went wrong! Please try again"];
        } else {
            return ["data" => json_decode($response->getBody(), true)["items"]];
        }
    }

    static function get_packages($repo_data) {
        $client = new Client(['base_uri' => Github::$BASE_URL]);
        $response = $client->get("repos/{$repo_data["owner"]}/{$repo_data["repo"]}/contents/package.json");
        $decoded_response = json_decode($response->getBody(), true);
        if(array_key_exists("message", $decoded_response)) {
            return ["error" => "package.json {$decoded_response["message"]}"];
        } else {
            $package_json = json_decode(base64_decode($decoded_response["content"]), true);
            return ["data" => array_keys($package_json)];
        }
    }

}