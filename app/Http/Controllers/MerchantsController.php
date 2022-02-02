<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Osiset\BasicShopifyAPI\BasicShopifyAPI;
use Osiset\BasicShopifyAPI\Options;
use Osiset\BasicShopifyAPI\Session;
use GuzzleHttp\Client;

class MerchantsController extends Controller
{

  public $token = 'ZXlKMGVYQWlPaUpLVjFRaUxDSmhiR2NpT2lKSVV6VXhNaUo5LmV5SjFjMlZ5WDJsa0lqb2lOVFF6T0RnaUxDSjFjMlZ5WDNSNWNHVWlPaUpEVEVsRlRsUWlMQ0prWVhSbElqb2lNakF5TWkwd01TMHhOQ0F5TURveE9Eb3hNU0o5LnpNalVEZmpsZkZ1bXJTRGNES0lSRTlqa183a2hNazU0U09FM1hwYzBfamFKTi1icTdQRUJmRm45bWJuOG1ibkhFRDlMUFRlZ3dER3U4azBQR1BhREd3';

  public function getMerchants(Request $request)
  {
    $shop = User::first();

    if(!$shop) {
      echo "App isn't installed to store.";
      exit();
    }

    $request_data = $request->json()->all();

    $query = <<<'GRAPHQL'
    query {
      affiliates {
        status, 
        name, 
        email, 
        rfsn_parameter
      }
    }
    GRAPHQL;

    return $this->graphql_query('https://graphql.refersion.com', $query, $this->token);
  }

  public function editPaypalAddress(Request $request)
  {
    $client = new Client();

    $request_data = $request->json()->all();

    $response = $client->request('POST', 'https://www.refersion.com/api/edit_affiliate', [
      'body' => '{"id":"6340222","paypal_email":"lucas@bluestout.com"}',
      'headers' => [
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
        'Refersion-Public-Key' => 'pub_85bc6da0224fa7d58342',
        'Refersion-Secret-Key' => 'sec_8a211f56d91c19bb5b2b',
      ],
    ]);

    return $response->getBody();
  }

  public function validateUser(Request $request)
  {
    $request_data = $request->json()->all();

    $query = <<<'GRAPHQL'
    query {
      affiliates(email: "lucas@bluestout.com") {
        id,
        status
      }
    }
    GRAPHQL;

    return $this->graphql_query('https://graphql.refersion.com', $query, $this->token);
  }

  public function graphql_query(string $endpoint, string $query, ?string $token = null): array
  {
    $headers = ['Content-Type: application/json'];
    if (null !== $token) {
        $headers[] = "X-Refersion-Key: $token";
    }

    if (false === $data = @file_get_contents($endpoint, false, stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => $headers,
            'content' => json_encode(['query' => $query]),
        ]
    ]))) {
        $error = error_get_last();
        throw new \ErrorException($error['message'], $error['type']);
    }

    return json_decode($data, true);
  }
}