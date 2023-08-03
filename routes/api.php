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
// routes/api.php

use App\Http\Controllers\IframeController;

Route::get('/iframe', [IframeController::class, 'getIframeUrl']);


Route::get('/pumpcsv/{date}', [PumpController::class,'pumpcsv']);

Route::get('/energy-usage', [EnergyUsageController::class,'getEnergyUsage']);

Route::get('/host', [Device_DetailsController::class, 'host']);

Route::get('/device', [Device_DetailsController::class, 'device']);

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

Route::get('/csv-biller/{folder}/{date}/{id}', [CsvDataController::class, 'csv_biller']);

Route::get('/jnmc_graph', [JnmcGraphController::class, 'getGraphData']);

Route::get('/jnmc_graph_new', [JnmcGraphController::class, 'getGraphDatanew']);




// Route::get('/jnmc_report','Testcontroller@generate_report');

Route::get('/jnmc_report', [Testcontroller::class, 'generate_report']);

Route::get('/hourly_graph/{date}/{host}/{device}', [Total_consumptionController::class, 'getKwhData']);