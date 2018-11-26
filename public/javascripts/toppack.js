angular.module("toppack", []).controller("ToppackCtrl", function ($scope, $http) {

    $scope.importedRepoIds = null;
    $scope.errorMessage = null;

    $http.get("imported_repo_ids")
        .then(function(successResponse) {
            $scope.importedRepoIds = successResponse.data.data;
        }, function (errorResponse) {
            $scope.errorMessage = "Please reload the page or contact support"
        });

    $scope.tabs = [
        { id: "repository", name: "Repository" },
        { id: "top_packages", name: "Top Packages" }
    ];

    function isImportedReposFetched() {
        return $scope.importedRepoIds != null;
    }
    $scope.isImportedReposFetched = isImportedReposFetched;

    $scope.currentTab = { id: "repository", name: "Repository" };
    $scope.searchQuery = null;
    $scope.searchQueryResult = [];

    function setCurrentTab(tab) {
        if(tab.id === "top_packages") {
            loadTopPackages();
        }
        $scope.currentTab = tab;
        $scope.searchQuery = "";
        $scope.searchQueryResult = [];
    }
    $scope.setCurrentTab = setCurrentTab;

    function loadTopPackages() {
        $http.get("top_packages")
            .then(function(successResponse) {
                $scope.searchQueryResult = successResponse.data.data;
                $scope.errorMessage = null;
            }, function (errorResponse) {
                $scope.errorMessage = errorResponse.error;
            });
    }

    function onSearchQueryChange(query) {
        let url = "search_repos";

        if($scope.currentTab.id === "top_packages") {
            url = "search_packages";
        }
        $scope.errorMessage = null;

        $http.get(url, {params: {query: query}})
            .then(function(successResponse) {
                $scope.searchQueryResult = successResponse.data.data;
                $scope.errorMessage = null;
            }, function (errorResponse) {
                $scope.errorMessage = errorResponse.error;
            });
    }
    $scope.onSearchQueryChange = onSearchQueryChange;

    function isSearchQueryResultNotEmpty() {
        return $scope.searchQueryResult.length > 0;
    }
    $scope.isSearchQueryResultNotEmpty = isSearchQueryResultNotEmpty;

    function isRepoImported(repoId) {
        let ids_len = $scope.importedRepoIds.length;
        for(let index=0; index < ids_len; index++) {
            if($scope.importedRepoIds[index].id === repoId) {
                return "true";
            }
        }
        return "false";
    }
    $scope.isRepoImported = isRepoImported;

    function importRepo(id, owner, repo, stargazers_count) {
        $scope.errorMessage = null;
        $http.post("import", {id: id, owner:owner, repo:repo, stargazers_count: stargazers_count})
            .then(function(successResponse) {
                if(successResponse.data.data) {
                    $scope.importedRepoIds.push({id: id});
                } else {
                    $scope.errorMessage = successResponse.data.error;
                }
            }, function (errorResponse) {
                $scope.errorMessage = errorResponse.error;
            });
    }
    $scope.importRepo = importRepo;

    function isRepositoryTab() {
        return $scope.currentTab.id === "repository";
    }
    $scope.isRepositoryTab = isRepositoryTab;

    function isTopPackagesTab() {
        return $scope.currentTab.id === "top_packages";
    }
    $scope.isTopPackagesTab = isTopPackagesTab;
});