<?php
$countries_whitelist = null;
if(isset($_GET['countries'])) {
  $countries_whitelist = explode(',',$_GET['countries']);
} else {
  $countries_whitelist = ['France','US','United Kingdom','Italy','Spain', 'Germany'];
}

$csv = file_get_contents("https://raw.githubusercontent.com/CSSEGISandData/COVID-19/master/csse_covid_19_data/csse_covid_19_time_series/time_series_covid19_deaths_global.csv");
$rows = explode("\n",$csv);
$original_data = array();
foreach($rows as $index => $row) {
  $csv_row = str_getcsv($row);
  if($index == 0) {
    $days_count = count($csv_row) - 4;
    continue;
  }

  $original_data[] = $csv_row;

}

$data = [];
$countries_list = [];

// Parse original data
foreach($original_data as $row) {

  $country = $row[1];
  if($country == 'Korea, South') $country = 'South Korea';
  if(!in_array($country,$countries_list)) $countries_list[] = $country;

  if($countries_whitelist && !in_array($country,$countries_whitelist)) continue;

  if(!$data[$country]) {
    $data[$country] = [];
  }

  $date = date('Y-m-d',strtotime('2020-01-22'));
  foreach($row as $index => $total_death) {
    if($index < 4) continue;

    $data[$country][$date]['total'] += $total_death;

    $date = strtotime("+1 day",strtotime($date));
    $date = date('Y-m-d', $date);
  }
}
foreach($data as $country => $dates) {
  $prev_date = null;
  foreach($dates as $date => $content) {
    if(!$prev_date) {
      $data[$country][$date]['per_day'] = $data[$country][$date]['total'];
    } else {
      $death_by_date = $data[$country][$date]['total'] - $data[$country][$prev_date]['total'];
      $data[$country][$date]['per_day'] = $death_by_date;
    }
    $prev_date = $date;
  }
}

$dataset = [];
foreach($data as $country => $dates) {

  $deaths_per_day = [];

  foreach($dates as $date) {
    if($date['per_day'] == 0) continue;
    $deaths_per_day[] = $date['per_day'];
  }

  $color = '#'.substr(md5(rand()), 0, 6);
  $set = [
    'label' => $country,
    'fill' => false,
    'lineTension' => 0.25,
    'borderColor' => $color,
    'borderCapStyle' => 'square',
    'borderDash' => [],
    'borderDashOffset' => 0.0,
    'borderJoinStyle' => 'miter',

    'pointBackgroundColor' => $color,
    'pointBorderWidth' => 1,
    'pointHoverRadius' => 8,
    'pointHoverBackgroundColor' => $color,
    'pointHoverBorderColor' => 'black',
    'pointHoverBorderWidth' => 2,
    'pointRadius' => 4,
    'pointHitRadius' => 10,
    'spanGaps' => true,
    'data' => $deaths_per_day,
    'hidden' => $countries_whitelist ? false:true

  ];
  $dataset[] = $set;
}

$json_dataset = json_encode($dataset);
?><!DOCTYPE html>
<html>
  <head>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
    <style>
      #myChart{
        background-color: #efefef;
        border-radius: 6px;
        box-shadow: 0 3rem 5rem -2rem rgba(0, 0, 0, 0.6);
        width: 100%;
      }

      #list-countries {
        display: none;
      }
      h1,h2 {
        text-transform: uppercase;
      }
    </style>
  </head>
  <body class="pt-5">
    <div class="container">
      <div class="row">
        <div class="col">
          <h1 class="text-center">
            COVID19 deaths per day in:
          </h1>
          <h2 class="text-center"><?= implode(', ',$countries_whitelist) ?></h2>
        </div>
      </div>
      <div class="row mt-5">
        <div class="col">
          <canvas id="myChart" width="400" height="150"></canvas>
        </div>
      </div>
      <div class="row mt-5">
        <div class="col">
          <p class="text-center"><button type="button" class="btn btn-light"id="more-countries">More countries</button></p>
        </div>
      </div>
      <div id="list-countries">
        <div class="row mt-5">
          <?php foreach($countries_list as $index => $country): ?>
            <div class="col">
              <input name="countries" class="form-check-input" type="checkbox" id="checkbox-<?= $index ?>" value="<?=$country?>" <?php if(in_array($country,$countries_whitelist)) echo 'checked'?>>
              <label class="form-check-label" for="checkbox-<?= $index ?>"><?=$country?></label>
            </div>
            <?php if(($index + 1) % 4 == 0): ?>
            </div>
            <div class="row">
            <?php endif; ?>
          <?php endforeach; ?>
          </div>
          <div class="row">
            <div class="col">
              <p class="text-center"><button type="button" class="btn btn-primary"id="refresh">Refresh</button></p>
            </div>
          </div>
        </div>

      </div>
      <div class="row mt-5">
        <div class="col">
          <p class="text-center">Data source: <a href="https://github.com/CSSEGISandData/COVID-19">2019 Novel Coronavirus COVID-19 (2019-nCoV) Data Repository by Johns Hopkins CSSE (GitHub)</a></p>
        </div>
      </div>
      <div class="row mt-5">
        <div class="col">
          <p class="text-center">Made by <a href="https://bastienlabelle.fr/">Bastien Labelle</a> — You can check the <span class="text-muted">(super messy)</span> code on <a href="https://github.com/bastienlabelle/covid19-death-per-country-per-day">GitHub</a></p>
        </div>
      </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.4.1.slim.min.js" integrity="sha384-J6qa4849blE2+poT4WnyKhv5vZF5SrPo0iEjwBvKU7imGFAV0wwj1yYfoRSJoZ+n" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.3/Chart.min.js" integrity="sha256-R4pqcOYV8lt7snxMQO/HSbVCFRPMdrhAFMH+vr9giYI=" crossorigin="anonymous"></script>
    <script>
    $('#more-countries').click(function() {
      ($('#list-countries').css('display') == 'none') ? $('#list-countries').css('display','block'): $('#list-countries').css('display','none');;
    });

    $('#refresh').click(function() {
      var countries = [];
            $.each($("input[name='countries']:checked"), function(){
                countries.push($(this).val());
            });
            window.location.href = window.location.pathname + '?countries=' + countries.join(',');
    });

    var canvas = document.getElementById("myChart");
    var ctx = canvas.getContext('2d');

    // Global Options:
    Chart.defaults.global.defaultFontColor = 'black';
    Chart.defaults.global.defaultFontSize = 16;

    var data = {
    labels: Array.apply(null, Array(<?= $days_count ?>)).map(function (_, i) {return i;}),
    datasets: <?= $json_dataset ?>
    };

    // Notice the scaleLabel at the same level as Ticks
    var options = {
    scales: {
              yAxes: [{
                  ticks: {
                      beginAtZero:true
                  },
                  scaleLabel: {
                       display: true,
                       labelString: 'deaths per day',
                       fontSize: 20
                    }
              }]
          }
    };

    // Chart declaration:
    var myChart = new Chart(ctx, {
    type: 'line',
    data: data,
    options: options
    });
    </script>
</html>
