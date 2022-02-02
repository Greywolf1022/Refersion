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

    return $this->graphql_query('https://graphql.refersion.com', $query, $_ENV['REFERSION_KEY']);
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
        'Refersion-Public-Key' => $_ENV['Refersion_Public_Key'],
        'Refersion-Secret-Key' => $_ENV['Refersion_Secret_Key'],
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

    return $this->graphql_query('https://graphql.refersion.com', $query, $_ENV['REFERSION_KEY']);
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