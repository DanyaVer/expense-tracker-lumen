<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use GuzzleHttp\Client;

class ReceiptParserController extends Controller
{
    /**
     * Parse a receipt image.
     *
     * This endpoint accepts an image file, prints its name (for testing),
     * and returns back sample receipt data with expenses.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function parseImageTest(Request $request)
    {
        // Validate that an image file is provided with allowed MIME types.
        $validator = Validator::make($request->all(), [
            'image' => 'required|file|mimes:jpeg,jpg,png,bmp'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error'   => 'Invalid image file.',
                'details' => $validator->errors()
            ], 422);
        }

        // Retrieve the uploaded file.
        $file = $request->file('image');
        $fileName = $file->getClientOriginalName();

        // Log the file name for debugging purposes.
        \Log::info("Received image file: " . $fileName);

        // For testing purposes, instead of sending the image to the Python API,
        // return a sample receipt object. (Later you can replace this with a Guzzle
        // call to your Python API.)
        $sampleData = [
            'file_name'       => $fileName,
            'date'            => date('Y-m-d', strtotime('-1 day')),
            'receipt_number'  => 'REC-TEST-001',
            'total'           => 100.50,
            'store'           => 'Sample Store',
            'currency_id'     => 147,
            'expenses'        => [
                [
                    'category_id'      => 7,
                    'spent_on'         => 'Coffee',
                    'amount'           => 5.25,
                    'transaction_date' => date('Y-m-d H:i:s', strtotime('-1 day')),
                    'remarks'          => 'Morning coffee'
                ],
                [
                    'category_id'      => 11,
                    'spent_on'         => 'Sandwich',
                    'amount'           => 8.75,
                    'transaction_date' => date('Y-m-d H:i:s', strtotime('-1 day')),
                    'remarks'          => 'Breakfast sandwich'
                ]
            ]
        ];

        return response()->json(['data' => $sampleData], 200);
    }
    
    /**
     * Parse a receipt image.
     *
     * This endpoint accepts an image file, sends it to an external Python API for receipt
     * extraction, validates the returned receipt object (including its expenses), and then returns it.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function parseImage(Request $request)
    {
        // Validate that the uploaded file is an image
        $validator = Validator::make($request->all(), [
            'image' => 'required|file|mimes:jpeg,jpg,png,bmp'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Invalid image file.',
                'details' => $validator->errors()
            ], 422);
        }

        // Retrieve the image from the request
        $file = $request->file('image');

        // Use Guzzle to send the image to the external Python API.
        // Make sure to set the RECEIPT_PARSER_API_URL in your .env file.
        $client = new Client();
        $externalUrl = env('RECEIPT_PARSER_API_URL');

        try {
            $response = $client->post($externalUrl, [
                'multipart' => [
                    [
                        'name'     => 'image',
                        'contents' => fopen($file->getRealPath(), 'r'),
                        'filename' => $file->getClientOriginalName()
                    ]
                ]
            ]);

            $body = $response->getBody();
            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'error' => 'Invalid JSON received from receipt parser API.'
                ], 500);
            }

            return response()->json(['data' => $data], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error processing image: ' . $e->getMessage()
            ], 500);
        }
    }
}
