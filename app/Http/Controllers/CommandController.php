<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\Process\Process;

class CommandController extends Controller
{
    public function runCommand(Request $request)
    {
        $command = $request->input('command');

        // Validate the command input
        $this->validate($request, [
            'command' => 'required|string'
        ]);

        // Execute the command
        $process = Process::fromShellCommandline($command);
        $process->run();

        // Get the command output
        $output = $process->getOutput();

        // Return the output as a response
        return response()->json([
            'output' => $output
        ]);
    }
}
