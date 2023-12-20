<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;

class Device_DetailsController extends Controller
{
    public function host()
    {
        // Build your SQL query
        $query = "SELECT DISTINCT client_id,client_name FROM device_details_wardha 
        WHERE client_id NOT IN ( 'v1','h501','h502','h503','h504','h505','h506','h507','h508','h509','h511','J1','J3') 
        AND client_name IS NOT NULL ORDER BY client_name";

        // Execute the query
        $results = DB::select($query);

        // Return the results
        return $results;
    }

    public function device(Request $request)
    {
        // Get the client_id from the request
        $client_id = $request->query('client_id');
        
        // Build your SQL query
        $query = "SELECT DISTINCT device_id, device_name FROM device_details_wardha 
        WHERE client_id = '$client_id' ORDER BY device_name";
    
        // Execute the query
        $results = DB::select($query);
    
        // Return the results
        return $results;
    }

    public function getParameters()
    {
        $parameters = [
            ['label' => 'Select Parameters', 'value' => ''],
            ['label' => 'KW_Total, R-phase, Y-phase, B-phase', 'value' => ['4', '5', '6', '7']],
            ['label' => 'KVAR_Total, R-phase, Y-phase, B-phase', 'value' => ['8', '9', '10', '11']],
            ['label' => 'PF_Ave, R-phase, Y-phase, B-phase', 'value' => ['12', '13', '14', '15']],
            ['label' => 'KVA_total, R-phase, Y-phase, B-phase', 'value' => ['16', '17', '18', '19']],
            ['label' => 'VLL_average, Vry-phase, Vyb-phase, Vbr-phase', 'value' => ['20', '21', '22', '23']],
            ['label' => 'VLN_average, Vr-phase, Vy-phase, Vb-phase', 'value' => ['24', '25', '26', '27']],
            ['label' => 'Current_Average, R-phase, Y-phase, B-phase', 'value' => ['28', '29', '30', '31']],
            ['label' => 'Frequency', 'value' => '32'],
            ['label' => 'KWh_Received', 'value' => '33'],
            ['label' => 'KVAh_Received', 'value' => '34'],
            ['label' => 'KVARh_Ind_Received', 'value' => '35'], 
            ['label' => 'KVARh_Cap_Received', 'value' => '36'],
            ['label' => 'KWh_Delivered', 'value' => '37'],
            ['label' => 'KVAh_Delivered', 'value' => '38'],
            ['label' => 'KVARh_Ind_Delivered', 'value' => '39'],
            ['label' => 'KVARh_Cap_Delivered', 'value' => '40'],
            ['label' => 'PF average Received', 'value' => '41'],
            ['label' => 'Amps average Received', 'value' => '42'],
            ['label' => 'PF average delivered', 'value' => '43'],
            ['label' => 'Amps average delivered', 'value' => '44'],
            ['label' => 'Neutral_current', 'value' => '45'],
            ['label' => 'Voltage-R-Harm, Voltage-Y-Harm, Voltage-B-Harm', 'value' => ['46', '47', '48']],
            ['label' => 'Current-R-Harm, Current-Y-Harm, Current-B-Harm, Level', 'value' => ['49', '50', '51', '52']]
            // ... Add more parameters as needed
        ];
    
        return response()->json(['parameters' => $parameters]);
    }
    public function getchillerParameters()
    {
        $parameters = [
            ['label' => 'Select Parameters', 'value' => '' ],
            ['label' => 'CW1-I, CW1-O, CW1-Diff, CW2-I, CW2-O, CW2-Diff', 'value' => ['2', '3', '4', '5','6','7'] ],
            ['label' =>'CDW1-I, CDW1-O, CDW1-Diff, CDW2-I, CDW2-O, CDW2-Diff', 'value' => ['8', '9', '10', '11','12','13']],
            ['label' => 'kw-1, kw-2, kw-sum', 'value' => ['14', '15', '16']],
			['label' => 'Flow Rate, Flow Commu.', 'value' => ['17', '18']],
            ['label' => 'RT', 'value' => ['19']],
            ['label' => 'KW / RT', 'value' => ['21']],
        ];
    
        return response()->json(['parameters' => $parameters]);
    }
    public function pump_host()
    {
        // Build your SQL query
        $query = "SELECT DISTINCT client_id,client_name FROM device_details_wardha 
        WHERE client_id  IN ( 'jch','pm2','pm3') 
        AND client_name IS NOT NULL ORDER BY client_name";

        // Execute the query
        $results = DB::select($query);

        // Return the results
        return $results;
    }
    public function pump_device(Request $request)
    {
        // Get the client_id from the request
        $client_id = $request->query('client_id');
        
        // Build your SQL query
        $query = "SELECT DISTINCT device_id, device_name FROM device_details_wardha 
        WHERE client_id = '$client_id' and device_id in (88,60,77) ORDER BY device_name";
    
        // Execute the query
        $results = DB::select($query);
    
        // Return the results
        return $results;
    }

    public function chiller_device(Request $request)
    {
        // Get the client_id from the request
        //$client_id = $request->query('client_id');
        
        // Build your SQL query
        $query = "SELECT DISTINCT device_id, device_name FROM device_details_wardha 
        WHERE client_id = 'CHILLER-AVBRH' and device_id in (60,61) ORDER BY device_name";
    
        // Execute the query
        $results = DB::select($query);
    
        // Return the results
        return $results;
    }
}
