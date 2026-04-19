<?php

class varsitymarket_github_services
{
    private $service_tokens;
    private $pwd;
    private $working_enviroment;
    private $account_tokens;
    private $credentials_email;
    private $credentials_name;
    private $cloudflare_zoneid;
    private $cloudflare_api;


    private function authenticate_token($token = false)
    {
        if ($token == false) {
            $token = $this->service_tokens;
        }

        if (function_exists('module_github_authentication') == false) {
            $ex = "module_github_authentication";
            $subset = $this->$ex($token);

            if (($subset !== null) && ($subset !== False)) {
                $this->account_tokens = json_decode($subset, JSON_PRETTY_PRINT);
                return True;
            }
            trigger_error("Corrupt Authentication Transactions");
            return false;
        }
    }

    private function module_github_authentication($token)
    {

        // GitHub API URL to check the token
        $url = "https://api.github.com/user";

        // Initialize cURL session
        $ch = curl_init($url);

        // Set cURL options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $token",
            "Accept: application/vnd.github+json",
            "X-GitHub-Api-Version: 2022-11-28",
            "User-Agent: PHP-cURL"
        ]);

        // Execute the cURL request
        $response = curl_exec($ch);
        // Check for errors
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
            return false;
        }

        // Get HTTP response code
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // Close the cURL session
        curl_close($ch);

        // Check if the token is valid based on the HTTP response code
        if ($httpCode === 200) {
            // Token is valid
            return $response;
        } else {
            // Token is invalid
            return false;
        }
    }


    public function list_enviroments()
    {
        if (($this->service_tokens == null)) {
            trigger_error('Authenticate Session To Continue');
            return Null;
        }

        if (function_exists('github_module_repository_list') !== true) {
            $function = 'github_module_repository_list';
            $exec = $this->$function($this->service_tokens);
            if ($exec) {
                if (is_array($exec)) {
                    return $exec;
                }
            }

            trigger_error('Cannot Retrieve Enviroment Data');
            return Null;
        }
    }

    private function github_module_repository_list($token)
    {
        // GitHub API URL for user repositories
        $url = "https://api.github.com/user/repos";

        // Initialize cURL session
        $ch = curl_init($url);

        // Set cURL options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Accept: application/vnd.github+json",
            "Authorization: Bearer $token",
            "X-GitHub-Api-Version: 2022-11-28",
            "User-Agent: PHP-cURL"
        ]);

        // Execute the cURL request
        $response = curl_exec($ch);

        // Check for errors
        if (curl_errno($ch)) {
            echo 'Error: ' . curl_error($ch);
            return null;
        }

        // Close the cURL session
        curl_close($ch);

        // Decode the response
        return json_decode($response, true);
    }

    function slugify($string)
    {
        // Convert to lowercase
        $string = strtolower($string);

        // Replace non-letter or digits by hyphen
        $string = preg_replace('~[^\pL\d]+~u', '-', $string);

        // Transliterate characters to ASCII
        $string = iconv('utf-8', 'us-ascii//TRANSLIT', $string);

        // Remove unwanted characters
        $string = preg_replace('~[^-\w]+~', '', $string);

        // Trim hyphens from the start and end
        $string = trim($string, '-');

        // Return the slug
        return $string;
    }

    public function create_enviroment($repository_name, $data, $verify = "STRICT")
    {
        if (($this->service_tokens == null)) {
            trigger_error('Authenticate Session To Continue');
            return Null;
        }

        #Check If The Repository Exists
        if ($verify == "IGNORE") {
            $verify = false;
        } else {
            $verify = TRUE;
            #Check With The Available Repositories 
            $env_list = $this->list_enviroments();
            foreach ($env_list as $e) {
                $check_name = $e['name'] ?? trigger_error('Could Not Verify Enviroment Name');
                if ($this->slugify(strtolower($check_name)) == $this->slugify(strtolower($repository_name))) {
                    trigger_error('Enviroment Name Already Exists');
                    return Null;
                }
            }
            $verify = FALSE;
        }


        $repository_meta_data = [];
        $repository_meta_data['name'] = $data['name'] ?? $repository_name;
        $repository_meta_data['description'] = $data['description'] ?? false;
        $repository_meta_data['private'] = $data['private'] ?? false;
        $repository_meta_data['homepage'] = $data['homepage'] ?? false;
        $repository_meta_data['hasIssues'] = $data['hasIssues'] ?? false;
        $repository_meta_data['hasProjects'] = $data['hasProjects'] ?? false;
        $repository_meta_data['hasWiki'] = $data['hasWiki'] ?? false;

        if ($verify == false) {
            if (function_exists('github_module_repository_create') == false) {
                $function = "github_module_repository_create";
                $this->working_enviroment = $repository_name;
                $results = $this->$function($repository_meta_data['name'], $repository_meta_data['description'], $repository_meta_data['homepage'], $repository_meta_data['private'], $repository_meta_data['hasIssues'], $repository_meta_data['hasProjects'], $repository_meta_data['hasWiki'], $this->service_tokens);
                $sanitise_name = $results['name'] ?? $this->slugify($repository_name);
                $this->working_enviroment = $sanitise_name;
                $this->configure_readme();
                #Prepare For The READ.me File For This Repository 

                $this->create_template();
                #Prepare The Temporary Webpages

                /*  Example Return 
                    {"id":949540455,"node_id":"R_kgDOOJjWZw","name":"Black-Sheep","full_name":"levidoc/Black-Sheep","private":true,"owner":{"login":"levidoc","id":157944600,"node_id":"U_kgDOCWoLGA","avatar_url":"https://avatars.githubusercontent.com/u/157944600?v=4","gravatar_id":"","url":"https://api.github.com/users/levidoc","html_url":"https://github.com/levidoc","followers_url":"https://api.github.com/users/levidoc/followers","following_url":"https://api.github.com/users/levidoc/following{/other_user}","gists_url":"https://api.github.com/users/levidoc/gists{/gist_id}","starred_url":"https://api.github.com/users/levidoc/starred{/owner}{/repo}","subscriptions_url":"https://api.github.com/users/levidoc/subscriptions","organizations_url":"https://api.github.com/users/levidoc/orgs","repos_url":"https://api.github.com/users/levidoc/repos","events_url":"https://api.github.com/users/levidoc/events{/privacy}","received_events_url":"https://api.github.com/users/levidoc/received_events","type":"User","user_view_type":"public","site_admin":false},"html_url":"https://github.com/levidoc/Black-Sheep","description":"Introducing blacksheep, a brand that elevates sophistication and style. Our clothing merges timeless elegance with modern flair, empowering individuality. We prioritize quality and sustainability, using eco-friendly materials. With diverse styles for every fashion enthusiast, blacksheep is a lifestyle choice for those who value artistry in fashion.","fork":false,"url":"https://api.github.com/repos/levidoc/Black-Sheep","forks_url":"https://api.github.com/repos/levidoc/Black-Sheep/forks","keys_url":"https://api.github.com/repos/levidoc/Black-Sheep/keys{/key_id}","collaborators_url":"https://api.github.com/repos/levidoc/Black-Sheep/collaborators{/collaborator}","teams_url":"https://api.github.com/repos/levidoc/Black-Sheep/teams","hooks_url":"https://api.github.com/repos/levidoc/Black-Sheep/hooks","issue_events_url":"https://api.github.com/repos/levidoc/Black-Sheep/issues/events{/number}","events_url":"https://api.github.com/repos/levidoc/Black-Sheep/events","assignees_url":"https://api.github.com/repos/levidoc/Black-Sheep/assignees{/user}","branches_url":"https://api.github.com/repos/levidoc/Black-Sheep/branches{/branch}","tags_url":"https://api.github.com/repos/levidoc/Black-Sheep/tags","blobs_url":"https://api.github.com/repos/levidoc/Black-Sheep/git/blobs{/sha}","git_tags_url":"https://api.github.com/repos/levidoc/Black-Sheep/git/tags{/sha}","git_refs_url":"https://api.github.com/repos/levidoc/Black-Sheep/git/refs{/sha}","trees_url":"https://api.github.com/repos/levidoc/Black-Sheep/git/trees{/sha}","statuses_url":"https://api.github.com/repos/levidoc/Black-Sheep/statuses/{sha}","languages_url":"https://api.github.com/repos/levidoc/Black-Sheep/languages","stargazers_url":"https://api.github.com/repos/levidoc/Black-Sheep/stargazers","contributors_url":"https://api.github.com/repos/levidoc/Black-Sheep/contributors","subscribers_url":"https://api.github.com/repos/levidoc/Black-Sheep/subscribers","subscription_url":"https://api.github.com/repos/levidoc/Black-Sheep/subscription","commits_url":"https://api.github.com/repos/levidoc/Black-Sheep/commits{/sha}","git_commits_url":"https://api.github.com/repos/levidoc/Black-Sheep/git/commits{/sha}","comments_url":"https://api.github.com/repos/levidoc/Black-Sheep/comments{/number}","issue_comment_url":"https://api.github.com/repos/levidoc/Black-Sheep/issues/comments{/number}","contents_url":"https://api.github.com/repos/levidoc/Black-Sheep/contents/{+path}","compare_url":"https://api.github.com/repos/levidoc/Black-Sheep/compare/{base}...{head}","merges_url":"https://api.github.com/repos/levidoc/Black-Sheep/merges","archive_url":"https://api.github.com/repos/levidoc/Black-Sheep/{archive_format}{/ref}","downloads_url":"https://api.github.com/repos/levidoc/Black-Sheep/downloads","issues_url":"https://api.github.com/repos/levidoc/Black-Sheep/issues{/number}","pulls_url":"https://api.github.com/repos/levidoc/Black-Sheep/pulls{/number}","milestones_url":"https://api.github.com/repos/levidoc/Black-Sheep/milestones{/number}","notifications_url":"https://api.github.com/repos/levidoc/Black-Sheep/notifications{?since,all,participating}","labels_url":"https://api.github.com/repos/levidoc/Black-Sheep/labels{/name}","releases_url":"https://api.github.com/repos/levidoc/Black-Sheep/releases{/id}","deployments_url":"https://api.github.com/repos/levidoc/Black-Sheep/deployments","created_at":"2025-03-16T17:27:20Z","updated_at":"2025-03-16T17:27:21Z","pushed_at":"2025-03-16T17:27:21Z","git_url":"git://github.com/levidoc/Black-Sheep.git","ssh_url":"git@github.com:levidoc/Black-Sheep.git","clone_url":"https://github.com/levidoc/Black-Sheep.git","svn_url":"https://github.com/levidoc/Black-Sheep","homepage":"https://stand.varsitymarket.shop/{USER-ID}","size":0,"stargazers_count":0,"watchers_count":0,"language":null,"has_issues":false,"has_projects":false,"has_downloads":true,"has_wiki":false,"has_pages":false,"has_discussions":false,"forks_count":0,"mirror_url":null,"archived":false,"disabled":false,"open_issues_count":0,"license":null,"allow_forking":true,"is_template":false,"web_commit_signoff_required":false,"topics":[],"visibility":"private","forks":0,"open_issues":0,"watchers":0,"default_branch":"main","permissions":{"admin":true,"maintain":true,"push":true,"triage":true,"pull":true},"allow_squash_merge":true,"allow_merge_commit":true,"allow_rebase_merge":true,"allow_auto_merge":false,"delete_branch_on_merge":false,"allow_update_branch":false,"use_squash_pr_title_as_default":false,"squash_merge_commit_message":"COMMIT_MESSAGES","squash_merge_commit_title":"COMMIT_OR_PR_TITLE","merge_commit_message":"PR_TITLE","merge_commit_title":"MERGE_MESSAGE","network_count":0,"subscribers_count":0}
                */
            }
        }
        #Create The Repository If Blank
    }


    private function github_module_repository_create($repoName, $description, $homepage, $private, $hasIssues, $hasProjects, $hasWiki, $token = 'DEFERED')
    {
        // GitHub API URL
        $url = "https://api.github.com/user/repos";

        if ($token == "DEFERED") {
            @trigger_error('Could Not Authenticate Token');
        }

        // Prepare the data for the POST request
        $data = [
            'name' => $repoName,
            'description' => $description,
            'homepage' => $homepage,
            'private' => $private,
            'has_issues' => $hasIssues,
            'has_projects' => $hasProjects,
            'has_wiki' => $hasWiki
        ];

        // Initialize cURL session
        $ch = curl_init($url);

        // Set cURL options
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Accept: application/vnd.github+json",
            "Authorization: Bearer $token",
            "X-GitHub-Api-Version: 2022-11-28",
            "Content-Type: application/json",
            "User-Agent: PHP-cURL"
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        // Execute the cURL request
        $response = curl_exec($ch);

        // Check for errors
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        } else {
            // Output the response
            echo $response;
        }

        // Close the cURL session
        curl_close($ch);
    }

    public function get_user_login()
    {
        return $this->account_tokens['login'] ?? null;
    }

    public function upload_dir($directory)
    {
        if (($this->service_tokens == null)) {
            trigger_error('Authenticate Session To Continue');
            return Null;
        }

        $starter_files = [
            '404_html' => [
                'path' => dirname(__FILE__) . "/src/templates/404.html",
                'desc' => 'This file is used to safely redirect users when they locate rescources outside the scope',
                'name' => '404.html',
            ],
            'index_html' => [
                'path' => dirname(__FILE__) . "/src/templates/installer-guide.html",
                'desc' => 'The installer guider for custom websites',
                'name' => 'guide/index.html',
            ],
            'promo.html' => [
                'path' => dirname(__FILE__) . "/src/templates/promo.html",
                'desc' => 'The Default Page For Vendors',
                'name' => 'index.html',
            ],
            'shop_html' => [
                'path' => dirname(__FILE__) . "/src/templates/shop.html",
                'desc' => 'The Shopping Page For The Vendors',
                'name' => 'shop/index.html',
            ],
        ];

        $function = "github_configure_file";

        $token = $this->service_tokens;
        $account_data = $this->account_tokens;
        //$owner = $account_data["login"] ?? trigger_error('Authentication Corrupt');
        $owner = $account_data["login"];
        $repo = $this->working_enviroment;
        $committerName = $this->credentials_name;
        $committerEmail = $this->credentials_email;

        foreach ($starter_files as $key => $value) {
            $path = $value['name'];
            $message = $value['desc'];
            $content = file_get_contents($value['path']);
            $exec = $this->$function($token, $owner, $repo, $path, $message, $committerName, $committerEmail, $content);
        }
    }

    public function create_template()
    {
        if (($this->service_tokens == null)) {
            trigger_error('Authenticate Session To Continue');
            return Null;
        }

        $starter_files = [
            '404_html' => [
                'path' => dirname(dirname(__FILE__)) . "/app/deploy/skel/404.html",
                'desc' => 'Initialising Deployment Lost Page',
                'name' => '404.html',
            ],
            'index_html' => [
                'path' => dirname(dirname(__FILE__)) . "/app/deploy/skel/index.html",
                'desc' => 'Initialising Deployment Index Page',
                'name' => '404.html',
            ]
        ];

        $function = "github_configure_file";

        $token = $this->service_tokens;
        $account_data = $this->account_tokens;
        //$owner = $account_data["login"] ?? trigger_error('Authentication Corrupt');
        $owner = $account_data["login"];
        $repo = $this->working_enviroment;
        $committerName = $this->credentials_name;
        $committerEmail = $this->credentials_email;

        foreach ($starter_files as $key => $value) {
            $path = $value['name'];
            $message = $value['desc'];
            $content = file_get_contents($value['path']);
            $exec = $this->$function($token, $owner, $repo, $path, $message, $committerName, $committerEmail, $content);
        }
    }

    function getFileSha($token, $owner, $repo, $path)
    {
        $url = "https://api.github.com/repos/$owner/$repo/contents/$path";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Accept: application/vnd.github+json",
            "Authorization: Bearer $token",
            "X-GitHub-Api-Version: 2022-11-28",
            "User-Agent: PHP-cURL"
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
            return null;
        }

        curl_close($ch);
        $data = json_decode($response, true);

        // Return the SHA if the file exists, otherwise return null
        return isset($data['sha']) ? $data['sha'] : null;
    }

    function github_configure_file($token, $owner, $repo, $path, $message, $committerName, $committerEmail, $content)
    {
        // GitHub API URL
        $url = "https://api.github.com/repos/$owner/$repo/contents/$path";
        $sha = $this->getFileSha($token, $owner, $repo, $path);

        // Prepare the data for the PUT request
        $data = [
            'message' => $message,
            'committer' => [
                'name' => $committerName,
                'email' => $committerEmail
            ],
            'content' => base64_encode($content), // Base64 encode the content
            'sha' => $sha
        ];

        // Initialize cURL session
        $ch = curl_init($url);

        // Set cURL options
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Accept: application/vnd.github+json",
            "Authorization: Bearer $token",
            "X-GitHub-Api-Version: 2022-11-28",
            "Content-Type: application/json",
            "User-Agent: PHP-cURL"
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        // Execute the cURL request
        $response = curl_exec($ch);

        // Check for errors
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        } else {
            // Output the response
            //echo $response;

            return $response;
        }

        // Close the cURL session
        curl_close($ch);
    }

    function draft_readme()
    {

        #This is to generate The Readme Template By Extracting The Data from the VM API
        $x = '
    <br />
    <div align="center">
    <a href="https://za.varsitymarket.tech/pages/vm-embedded-sites">
        <img src="https://za.varsitymarket.tech/img/favicon.png" alt="vmtech" style="border-radius:4px">
    </a>
    <h1>BLACKSHEEP</h1>
    <p align="center">
        Micro Website Package
    </p>
    </div>

    <h3 align="center">Varsity Market Sites</h3>

    Empowering your entrepreneurial dreams with our Vendor console, the ultimate toolkit for young, small-scale business owners. We provide a streamlined platform to manage your finances, track inventory, connect with customers, and gain valuable insights to grow your venture. From invoicing to marketing and sales support, Vendor console simplifies the complexities of running a business, allowing you to focus on what you do best: building your brand and achieving your goals. Start your journey to success today with Vendor Console and unlock the potential of your small business.

    ';
        return $x;
    }

    public function configure_readme($dir = '')
    {
        if (($this->service_tokens == null)) {
            trigger_error('Authenticate Session To Continue');
            return Null;
        }

        if (function_exists('draft_readme') == false) {
            $e = 'draft_readme';
            $readme_template = $this->$e();
            if (function_exists('github_configure_file') == false) {
                $function = "github_configure_file";

                $token = $this->service_tokens;
                $account_data = $this->account_tokens;
                //$owner = $account_data["login"] ?? trigger_error('Authentication Corrupt');
                $owner = $account_data["login"];
                $repo = $this->working_enviroment;
                $path = $dir . 'README.md';
                $message = "Reconfigured Readme File";
                $committerName = $this->credentials_name;
                $committerEmail = $this->credentials_email;
                $content = $readme_template;
                $exec = $this->$function($token, $owner, $repo, $path, $message, $committerName, $committerEmail, $content);

            }
        }
    }

    function update_github_repository($owner, $repo, $token, $data)
    {

        // GitHub API endpoint for updating a repository
        $url = "https://api.github.com/repos/$owner/$repo";

        // Initialize cURL session
        $ch = curl_init();

        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Accept: application/vnd.github+json",
            "Authorization: Bearer $token",
            "X-GitHub-Api-Version: 2022-11-28",
            "User-Agent: PHP-cURL" // User-Agent header is required by GitHub's API
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        // Execute cURL request
        $response = curl_exec($ch);

        // Check for errors
        if (curl_errno($ch)) {
            echo "cURL error: " . curl_error($ch);
            curl_close($ch);
            return null;
        }

        // Get HTTP status code
        $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // Close cURL session
        curl_close($ch);

        // Ensure we got a successful response (status code 200)
        if ($httpStatusCode === 200) {
            return json_decode($response, true); // Decode JSON response into an associative array
        } else {
            trigger_error("Could Not Execute Request");
            exit();

            echo "GitHub API returned status code: $httpStatusCode\n";
            echo "Response: $response\n";
            return null;
        }
    }

    public function update_enviroment($name, $data_set)
    {
        $function = 'update_github_repository';

        $account_data = $this->account_tokens;
        //$owner = $account_data["login"] ?? trigger_error('Authentication Corrupt');
        $owner = $account_data["login"];
        $repo = $this->working_enviroment;
        $token = $this->service_tokens;
        $data = $data_set;

        $exec = $this->$function($owner, $repo, $token, $data);
    }

    function configure_Github_Pages($owner, $repo, $token, $cname, $branch = 'main', $path = '/')
    {
        $url = "https://api.github.com/repos/{$owner}/{$repo}/pages";

        $data = [
            'cname' => $cname,
            'source' => [
                'branch' => $branch,
                'path' => $path,
            ],
        ];

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_HTTPHEADER => [
                'Accept: application/vnd.github+json',
                "Authorization: Bearer {$token}",
                'X-GitHub-Api-Version: 2022-11-28',
                'Content-Type: application/json',
                "User-Agent: PHP-cURL"
            ],
            CURLOPT_POSTFIELDS => json_encode($data),
        ];

        $ch = curl_init();
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        //echo($response); 

        return [
            'response' => json_decode($response, true),
            'http_code' => $httpCode,
        ];
    }

    function convertToDomainFormat($wildString)
    {
        // Replace '+' with '-'
        $formattedString = str_replace('+', '-', $wildString);

        // Convert the string to lowercase
        $formattedString = strtolower($formattedString);

        // Remove any invalid characters (only keep alphanumeric, hyphens, and dots)
        $formattedString = preg_replace('/[^a-z0-9\-\.]/', '', $formattedString);

        // Remove multiple consecutive hyphens
        $formattedString = preg_replace('/-{2,}/', '-', $formattedString);

        // Trim hyphens from the start and end
        $formattedString = trim($formattedString, '-');

        return $formattedString;
    }

    public function enable_domain($domain, $repo = false, $token = false)
    {
        $function = "configure_Github_Pages";
        $account_data = $this->account_tokens;
        //$owner = $account_data["login"] ?? trigger_error('Authentication Corrupt');
        $owner = $account_data["login"];
        if ($repo == false) {
            $repo = $this->working_enviroment;
        }

        if ($token == false) {
            $token = $this->service_tokens;
        }
        $cname = $this->convertToDomainFormat($domain);
        $branch = 'main';
        $path = '/';

        @$side_quest = $this->enable_pages_worker($owner, $repo, $token, $branch, $path);

        $exec = $this->$function($owner, $repo, $token, $cname, $branch, $path);
    }

    public function enable_pages_worker($owner, $repo, $token, $branch = 'main', $path = '/')
    {

        function enableGitHubPages($owner, $repo, $token, $branch, $path)
        {
            $url = "https://api.github.com/repos/$owner/$repo/pages";
            $data = json_encode([
                'source' => [
                    'branch' => $branch,
                    'path' => $path
                ]
            ]);

            $headers = [
                "Accept: application/vnd.github+json",
                "Authorization: Bearer $token",
                "X-GitHub-Api-Version: 2022-11-28",
                "Content-Type: application/json",
                "User-Agent: PHP-cURL"
            ];

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            curl_close($ch);

            return [
                'response' => $response,
                'http_code' => $httpCode
            ];
        }


        return enableGitHubPages($owner, $repo, $token, $branch, $path);
    }

    public function force_enable_domain($token, $repo, $owner, $domain)
    {
        $function = "configure_Github_Pages";
        $cname = ($domain);
        $branch = 'main';
        $path = '/';
        $exec = $this->$function($owner, $repo, $token, $cname, $branch, $path);
    }

    function craft_dns_record($zoneId, $apiKey, $name, $content, $type = "CNAME", $comment = 'Online Store Automation Task', $ttl = 3600, $proxied = false)
    {
        $url = "https://api.cloudflare.com/client/v4/zones/{$zoneId}/dns_records";

        $data = [
            'comment' => $comment,
            'content' => $content,
            'name' => $name,
            'proxied' => $proxied,
            'ttl' => $ttl,
            'type' => $type,
        ];

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                "Authorization: Bearer {$apiKey}",
                // "X-Auth-Key: {$apiKey}",
            ],
            CURLOPT_POSTFIELDS => json_encode($data),
        ];

        $ch = curl_init();
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'response' => json_decode($response, true),
            'http_code' => $httpCode,
        ];
    }

    public function configure_subdomain($domain, $ip = 'levidoc.github.io')
    {
        $function = "craft_dns_record";
        $zoneId = $this->cloudflare_zoneid;
        $apiKey = $this->cloudflare_api;
        $name = $this->convertToDomainFormat($domain);
        $content = $ip;
        $ttl = 3600;
        $proxied = false;

        $exec = $this->$function($zoneId, $apiKey, $name, $content, $ttl, $proxied);
    }

    public function __construct($account_token)
    {
        #Schedule Sub Domain Configuration 
        $this->cloudflare_zoneid = $_SERVER['__CLOUDFLARE_ZONEID__'];
        $this->cloudflare_api = $_SERVER['__CLOUDFLARE_TOKEN__'];

        //set error handler
        #set_error_handler('customError');
        $this->credentials_email = "hastings@varsitymarket.tech";
        $this->credentials_name = "varsitymarket-technologies";
        $this->pwd = dirname(__FILE__) . "/";
        if (($this->service_tokens == null)) {
            $auth_session = $this->authenticate_token($account_token);
            #This function Authenticates The Provided Personal Authentication Token (PAT)
            if ($auth_session == true) {
                $this->service_tokens = $account_token;
                #Reconfigure Service Tokens 
                #echo('Congrats Done: '.$this->account_tokens); 
            }
        }
    }

    public function get_account_data()
    {
        return $this->account_tokens;
    }
}
