<?php

namespace Cvd\Controller;

use Cvd\Service\Database;
use Twig\Loader\FileSystemLoader;
use Twig\Environment;

class CasesController
{
    public $db= null;
    public $twig;

    public function __construct(\PDO $db ) {
        $this->db = $db;

        $loader = new FileSystemLoader('src/Template');
        $this->twig = new Environment($loader,[
            'cache' => 'src/Cache',
            'debug' => true
        ]);
        $this->twig->addExtension(new \Twig\Extension\DebugExtension());

    }

    public function index($args = null) {
       
        $query = 'SELECT SUM(new_infections) as count, "last_one_month_count" as result FROM `cases` WHERE last_updated > DATE_SUB(NOW(), INTERVAL 1 MONTH) 
            UNION ALL
            SELECT SUM(new_infections) as count, "last_one_week_count" as result FROM `cases` WHERE last_updated > DATE_SUB(NOW(), INTERVAL 1 WEEK) 
            UNION ALL
            SELECT SUM(new_infections) as count, "last_one_day_count" as result FROM `cases` WHERE last_updated > DATE_SUB(NOW(), INTERVAL 1 DAY)';

        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
       
        return $this->twig->render('cases\view_stat.html.twig',['data' => [
            'last_one_month_count' => $result[0]['count'] ?? 0,
            'last_one_week_count' => $result[1]['count'] ?? 0,
            'last_one_day_count' => $result[2]['count'] ?? 0,
        ]]);
    }

    public function dailyCaseReport($args = null) {
        $date = (empty($args[0]) || $args==null) ? date('Y-m-d') : $args[0];

        $query = 'SELECT new_infections, new_deaths, new_recovered FROM `cases` WHERE DATE(last_updated) = :report_date';
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':report_date', $date,\PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $this->twig->render('cases\daily_case_report.html.twig',['data' => [
            'new_infections' => $result['new_infections'] ?? 0,
            'new_deaths' => $result['new_deaths'] ?? 0,
            'new_recovered' => $result['new_recovered'] ?? 0,
        ]]);

    }

    public function updateData($args = null) {
    
        $stmt = $this->db->prepare('SELECT id,DATE(last_updated) as date FROM cases order by id DESC limit 1');
        $stmt->execute();
        
        $fetchLastDateRecord = $stmt->fetch(\PDO::FETCH_OBJ);
        $from_date =  $fetchLastDateRecord->date ?? '2020-01-01';
        $to_date =  date('Y-m-d');
        if($from_date != $to_date) {
            $stmt = $this->db->prepare('DELETE FROM cases WHERE id = :delete_id');
            $stmt->execute([':delete_id' => $fetchLastDateRecord->id]);
            
            $curl = curl_init();
            curl_setopt_array($curl, array(
            CURLOPT_URL =>  'http://api.coronatracker.com/v3/analytics/newcases/country?countryCode=IN&startDate='.$from_date.'&endDate='.$to_date,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => "GET"
            ));
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            $response = json_decode($response,TRUE);

            $query = 'INSERT INTO cases(country, last_updated, new_infections, new_deaths, new_recovered) VALUES (:country, :last_updated, :new_infections, :new_deaths, :new_recovered) ';

            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':country', $country);
            $stmt->bindParam(':last_updated', $last_updated);
            $stmt->bindParam(':new_infections', $new_infections);
            $stmt->bindParam(':new_deaths', $new_deaths);
            $stmt->bindParam(':new_recovered', $new_recovered);

            foreach($response as $key => $value) {
                $country = $value['country'] ?? 0;
                $last_updated = $value['last_updated'] ?? 0;
                $new_infections = $value['new_infections'] ?? 0;
                $new_deaths = $value['new_deaths'] ?? 0;
                $new_recovered = $value['new_recovered'] ?? 0;
                $stmt->execute();
                
            }        
        }
        return 'success';
        
    }
}
