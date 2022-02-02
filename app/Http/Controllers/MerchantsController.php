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

    return $this->graphql_query('https://graphql.refersion.com', $query, [], $_ENV['REFERSION_KEY']);
  }

  public function editPaypalAddress(Request $request)
  {
    $client = new Client();

    $affiliate_id = $request->id;
    $paypal_email = $request->paypal_email;

    $response = $client->request('POST', 'https://www.refersion.com/api/edit_affiliate', [
      'body' => '{"id":"'. $affiliate_id .'","paypal_email":"'. $paypal_email .'"}',
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
    $email = $request->email;

    $query = <<<'GRAPHQL'
    query validateUser($email: String!) {
      affiliates(email: $email) {
        id,
        status
      }
    }
    GRAPHQL;

    return $this->graphql_query('https://graphql.refersion.com', $query, ['email' => $email], $_ENV['REFERSION_KEY']);
  }

  public function graphql_query(string $endpoint, string $query, array $variables = [], ?string $token = null): array
  {
    $headers = ['Content-Type: application/json'];
    if (null !== $token) {
        $headers[] = "X-Refersion-Key: $token";
    }

    if (false === $data = @file_get_contents($endpoint, false, stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => $headers,
            'content' => json_encode(['query' => $query, 'variables' => $variables]),
        ]
    ]))) {
        $error = error_get_last();
        throw new \ErrorException($error['message'], $error['type']);
    }

    return json_decode($data, true);
  }
}