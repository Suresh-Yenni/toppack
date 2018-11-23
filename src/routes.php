<?php

use Slim\Http\Request;
use Slim\Http\Response;
require __DIR__ . '/services/github.php';

// Routes

$app->get('/hello/[{name}]', function (Request $request, Response $response, array $args) {
    // Sample log message
    $this->logger->info("Slim-Skeleton '/' route");

    // Render index view
    return $this->renderer->render($response, 'index.phtml', $args);
});

$app->get('/dbs', function (Request $request, Response $response) {
    $result = "Available databases:<br>";
    $dbs = $this->db->query("show databases");
    $result = ["data" => $dbs->fetchAll()];
    $this->logger->info($result);
    return $response->withJson($result);
});

$app->post('/search_repos', function (Request $request, Response $response) {
    $params = $request->getParams();
    if(array_key_exists("query", $params)) {
        $result = Github::search_repo($params["query"], "js");
    } else {
        $result = ["error" => "Please provide search query."];
    }
    return $response->withJson($result);
});

$app->post('/import', function(Request $request, Response $response) {
    $params = $request->getParams();
    $expected_keys = ["id", "owner", "repo", "stargazers_count"];
    $params_keys = array_keys($params);
    if(sizeof(array_intersect($expected_keys, $params_keys)) != 4) {
        $result = ["error" => "Please provide repo_id, repo_name, stargazers_count and owner_name"];
    } else {
        $packages = Github::get_packages($params)["data"];
        $this->logger->info("Packages:::::", $packages);

        // inserting into imported_repos
        $query = "insert into imported_repos(id, repo, owner, stargazers_count) values(:id, :repo, :owner, :stargazers_count)";
        $sql = $this->db->prepare($query);
        $sql->execute($params);

        // inserting into packages
        $sub_query = str_repeat("(?)", sizeof($packages));
        $sub_query = str_replace(")(", "),(", $sub_query);
        $query = "insert into packages(name) values $sub_query";
        $this->logger->info("Query::::: $query");
        $sql = $this->db->prepare($query);
        $sql->execute($packages);

        // getting all new inserted packages_ids
        $sub_query = str_repeat("?, ", sizeof($packages));
        $sub_query = rtrim($sub_query, ", ");
        $query = "select id from packages where name in ($sub_query)";
        $this->logger->info("Query::::: $query");
        $sql = $this->db->prepare($query);
        $sql->execute($packages);
        $package_rows = $sql->fetchAll();
        $this->logger->info("package_rows:::::", $package_rows);

        // inserting into imported_repos_packages
        $new_rows_values = [];
        foreach ($package_rows as $package_row) {
            array_push($new_rows_values, $params["id"], $package_row["id"]);
        }
        $sub_query = str_repeat("(?, ?)", sizeof($new_rows_values)/2);
        $sub_query = str_replace(")(", "),(", $sub_query);
        $query = "insert into imported_repos_packages(repo_id, package_id) values $sub_query";
        $this->logger->info("Query::::: $query");
        $sql = $this->db->prepare($query);
        $sql->execute($new_rows_values);

        $result = ["data" => "Imported Successfully"];
    }
    return $response->withJson($result);
});

$app->get('/top_packages', function(Request $request, Response $response) {
    $ordered_top_packages_query = "select * from packages where id in (select package_id from imported_repos_packages group by package_id order by count(repo_id) DESC)";
    $top_packages = $this->db->query($ordered_top_packages_query);
    return $response->withJson(["data" => $top_packages->fetchAll()]);
});

$app->post('/search_packages', function(Request $request, Response $response) {
    $params = $request->getParams();
    if(array_key_exists("query", $params)) {
        $search_term = "{$params["query"]}%";
        $this->logger->info("Search term:::::$search_term");
        $sql = $this->db->prepare("select * from packages where name like :search_term");
        $sql->execute(["search_term" => $search_term]);
        $packages = $sql->fetchAll();
        $result = ["data" => $packages];
    } else {
        $result = ["error" => "Please provide search query."];
    }
    return $response->withJson($result);
});
