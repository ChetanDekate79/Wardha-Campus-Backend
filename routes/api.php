<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CsvDataController;
use App\Http\Controllers\JnmcGraphController;
use App\Http\Controllers\Testcontroller;
use App\Http\Controllers\CustomGraphController;
use App\Http\Controllers\DataController;
use App\Http\Controllers\PythonController;
use App\Http\Controllers\CommandController;
use App\Http\Controllers\ExcelController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\LoginControllernew;
use App\Http\Controllers\Device_DetailsController;
use App\Http\Controllers\EnergyUsageController;
use App\Http\Controllers\PumpController;
use App\Http\Controllers\Total_consumptionController;
use App\Http\Controllers\MeterStatusController;
use App\Http\Controllers\Pumpcsv_Controller;
use App\Http\Controllers\IframeController;
use App\Http\Controllers\Hourly_Graph_Controller;
use App\Http\Controllers\Generate_HourlyData_Controller;
use App\Http\Controllers\Chiller_Controller;
use App\Http\Controllers\Chiller_csv_Controller;
use App\Http\Controllers\WeatherController;

Route::get('/chiller-monthly', [Chiller_csv_Controller::class, 'getChillerData_monthly']);


Route::get('/weather/current', [WeatherController::class, 'getCurrentWeather']);

Route::get('/chiller_report/{folder}/{date}', [Chiller_csv_Controller::class, 'chiller_report']);


Route::get('/generate_hourly_data_all/{folder}/{date}', [Generate_HourlyData_Controller::class, 'generate_data_all']);

Route::get('/generate_hourly_data/{folder}/{date}/{id}', [Generate_HourlyData_Controller::class, 'generate_data']);

Route::get('/generate-hourly-data', [Hourly_Graph_Controller::class, 'generateHourlyGraphData']);

Route::get('/hourly_graph/{date}/{host}/{device}', [Hourly_Graph_Controller::class, 'hourly_graph']);

Route::get('/pump_report/{folder}/{date}/{id}', [Pumpcsv_Controller::class, 'pump_report']);

Route::get('/iframe', [IframeController::class, 'getIframeUrl']);

Route::get('current_datetime/{folder}/{date}', [MeterStatusController::class, 'processCsv']);

Route::get('/pumpcsv/{date}', [PumpController::class,'pumpcsv']);

Route::get('/energy-usage', [EnergyUsageController::class,'getEnergyUsage']);

Route::get('/host', [Device_DetailsController::class, 'host']);

Route::get('/device', [Device_DetailsController::class, 'device']);

Route::get('/parameters', [Device_DetailsController::class, 'getParameters']);

Route::get('/getchillerParameters', [Device_DetailsController::class, 'getchillerParameters']);

Route::get('/pump_host', [Device_DetailsController::class, 'pump_host']);

Route::get('/pump_device', [Device_DetailsController::class, 'pump_device']);

Route::get('/chiller_device', [Device_DetailsController::class, 'chiller_device']);

Route::post('/loginnew', [LoginControllernew::class, 'executeQuery']);

Route::get('/login', [LoginController::class, 'executeQuery']);


Route::get('/excel', [ExcelController::class, 'readExcel']);

Route::post('/run-command', [CommandController::class, 'runCommand']);

Route::post('/execute-python', [PythonController::class,'execute']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/api/data', [CsvDataController::class, 'index']);

Route::get('/csv-data/{folder}/{date}/{id}', [CsvDataController::class, 'getByFolderDateId']);

Route::get('/chiller-data/{folder}/{date}', [Chiller_Controller::class, 'getByFolderDateId']);

Route::get('/csv-biller/{folder}/{date}/{id}', [CsvDataController::class, 'csv_biller']);

Route::get('/jnmc_graph', [JnmcGraphController::class, 'getGraphData']);

Route::get('/jnmc_graph_new', [JnmcGraphController::class, 'getGraphDatanew']);




// Route::get('/jnmc_report','Testcontroller@generate_report');

Route::get('/jnmc_report', [Testcontroller::class, 'generate_report']);

Route::get('/hourly_graph_old/{date}/{host}/{device}', [Total_consumptionController::class, 'getKwhData']);