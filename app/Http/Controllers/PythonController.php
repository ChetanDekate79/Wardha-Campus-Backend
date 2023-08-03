<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PythonController extends Controller
{
    public function execute(Request $request)
    {
        // Increase the maximum execution time to 120 seconds (2 minutes)
        set_time_limit(160);

        // Validate the request data
        $validator = Validator::make($request->all(), [
            'var1' => 'required',
            'var2' => 'required',
            'var3' => 'required'
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid request data.',
                'errors' => $validator->errors()
            ], 400);
        }

        // Get the variables from the request
        $var1 = $request->input('var1');
        $var2 = $request->input('var2');
        $var3 = $request->input('var3');

        // Define the path to the Python script
        $pythonScript = 'C:/Users/Chetan/Documents/python/Final.py';

        // Prepare the command with the variables as arguments
        $command = 'python ' . $pythonScript . ' ' . escapeshellarg($var1) . ' ' . escapeshellarg($var2) . ' ' . escapeshellarg($var3);

        // Execute the Python script
        exec($command, $output, $returnVar);

        // Check the execution status
        if ($returnVar === 0) {
            // Python script executed successfully
            return response()->json([
                'status' => 'success',
                'output' => $output
            ]);
        } else {
            // Python script execution failed
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to execute Python script.'
            ], 500);
        }
    }
}
