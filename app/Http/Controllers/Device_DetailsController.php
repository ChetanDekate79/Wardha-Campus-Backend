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
    
}
