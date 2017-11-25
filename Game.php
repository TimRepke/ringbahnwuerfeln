<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

class Game {
    
    private $name, $path, $game_file_path, $game;
    
    private $block_time = 10*60;
    
    function __construct($name, $path = 'gamefiles') {
        $this->name = $name;
        $this->path = $path;
        $this->game_file_path = $this->load_game_file_path();
        $this->game = $this->load_game_file();
    }
    
    private function load_game_file_path() {
        $fname = preg_replace("/[^a-z0-9 ]/", '', strtolower($this->name));;
        $filename = '/'.$this->path . '/' . $fname . '.json';
        if (!file_exists($filename) && isset($_GET['start']) && $this->has_admin_rights()) {
            file_put_contents($filename, '{
                "teams": {},
                "name": "'.$this->name.'"",
                "start": "'.$_GET['start'].'"
            }');
        }
        return __DIR__.$filename;
    }
    
    private function has_admin_rights() {
        if (isset($_GET['adminToken'])) {
            $adminToken = file_get_contents($this->path.'/adminToken');
            return $_GET['adminToken'] == $adminToken;
        }
        return false;
    }
    
    private function load_game_file() {
        $json_string = file_get_contents($this->game_file_path);
        return json_decode($json_string, true);
    }
    
    private function save_game_file() {
        $json_string = json_encode($this->game, JSON_PRETTY_PRINT);
        if (strlen($json_string) < 2000000){
            file_put_contents($this->game_file_path, $json_string);
        } else {
            $this->render_error('FILE_TOO_LARGE');
        }
        
    }
    
    public function render_response($obj) {
        header('Content-Type: application/json');
        echo json_encode($obj);
    }
    
    private function render_error($msg) {
        http_response_code(400);
        $ret = [
            'error' => $msg,
            'params' => []
        ];
        foreach ($_GET as $k => $v) {
            $ret['params'][$k] = $v;
        }
        $this->render_response($ret);
    }
    
    private function check_team_auth() {
        return (isset($_GET['name']) && isset($_GET['secret']) && isset($this->game['teams'][$_GET['name']]) &&
            isset($this->game['teams'][$_GET['name']]['secret']) &&
            $this->game['teams'][$_GET['name']]['secret'] == $_GET['secret']);
    }
    
    public function add_team() {
        if (isset($_GET['name']) && isset($_GET['secret']) && !isset($this->game['teams'][$_GET['name']])) {
            $this->game['teams'][$_GET['name']] = [
                'name' => $_GET['name'],
                'secret' => $_GET['secret'],
                'stops' => [[
                    'stop' => 0,
                    'beer_size' => 0.0,
                    'rolled_time' => time(),
                    'rolled_steps' => 0
                ]]
            ];
            $this->save_game_file();
            $this->render_response([
                'msg' => 'SUCCESS',
                'name' => $_GET['name'],
                'secret' => $_GET['secret']
            ]);
        } else {
            $this->render_error('TEAM_EXISTS');
        }
    }
    
    private function get_beer_size($steps) {
        return ($steps <= 3) ? 0.33 : 0.5;
    }
    
    public function get_next_stop() {
        if ($this->check_team_auth()) {
            $team = $this->game['teams'][$_GET['name']];
            $last_stop = $team['stops'][count($team['stops']) - 1];
            if ((count($team['stops']) > 1) && (time() - $last_stop['rolled_time']) < $this->block_time){
                $this->render_response([
                    'team' => $team['name'],
                    'secret' => $team['secret'],
                    'blocked_till' => $last_stop['rolled_time']+$this->block_time
                ]);
            } else {
                $steps = mt_rand(1, 6);
                $new_stop = [
                    'stop' => $last_stop['stop'] + $steps,
                    'rolled_time' => time(),
                    'rolled_steps' => $steps,
                    'beer_size' => $this->get_beer_size($steps)
                ];
                array_push($this->game['teams'][$_GET['name']]['stops'], $new_stop);
                
                $this->save_game_file();
                
                $this->render_response([
                    'team' => $team['name'],
                    'secret' => $team['secret'],
                    'steps' => $steps,
                    'beer_size' => $new_stop['beer_size'],
                    'current_stop' => $last_stop['stop'],
                    'next_stop' => $new_stop['stop'],
                    'blocked_till' => $new_stop['rolled_time']+$this->block_time
                ]);
            }
        } else {
            $this->render_error('FAILED_TEAM_AUTH');
        }
    }
    
    public function get_info() {
        foreach ($this->game['teams'] as $name => $team) {
            unset($this->game['teams'][$name]['secret']);
        }
        $this->render_response($this->game);
    }
    
    public function get_games() {
        $games = [];
        foreach (scandir($this->path) as $file) {
            if(!in_array($file,['.htaccess', 'adminToken', '.', '..'])){
                array_push($games, str_replace('.json', '',$file));
            }
        }
        $this->render_response($games);
    }
}

// to add new game, open domain/Game.php?g=<name>&adminToken=<secretToken>&start=<startStationName>
// the <secretToken> is in Game->path/adminToken (no line break!)

if (isset($_GET['t'])) {
    $game = new Game(isset($_GET['g']) ? $_GET['g'] : 'test');
    switch ($_GET['t']) {
        case 'roll':
            $game->get_next_stop();
            break;
        case 'newTeam':
            $game->add_team();
            break;
        case 'info':
            $game->get_info();
            break;
        case 'games':
            $game->get_games();
            break;
        default:
            break;
    }
}