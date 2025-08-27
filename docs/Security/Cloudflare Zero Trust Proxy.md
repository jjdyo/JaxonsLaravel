# Cloudflare Zero Trust Access for API

## Overview

This document provides information about accessing the API through Cloudflare Zero Trust (formerly Cloudflare Access). Cloudflare Zero Trust is a security solution that adds an additional layer of authentication and protection to our API endpoints.

## What is Cloudflare Zero Trust?

Cloudflare Zero Trust is a security framework that verifies each request to protected applications before granting access. Unlike traditional security models that trust anyone inside the network perimeter, Zero Trust requires verification for every user and every request, regardless of where they originate.

Cloudflare Tunnels (formerly Argo Tunnel) create a secure connection between our application and Cloudflare's edge, without the need for public IP addresses or opening ports in the firewall. This provides enhanced security by eliminating direct exposure of our application to the internet.

## API Access URL

The API is accessible through the following Zero Trust protected URL:

```
https://api-laravel.jaxonville.com
```

## Authentication Requirements

To access the API through Cloudflare Zero Trust, you need:

1. **Cloudflare Service Token** - A pair of credentials (Client ID and Client Secret) that authenticate your request to the Cloudflare Access layer
2. **Application API Token** - Your regular application API token that authenticates your request to the application itself

### Required Headers

Each request to the API must include the following headers:

| Header                    | Description                                                       |
|---------------------------|-------------------------------------------------------------------|
| `CF-Access-Client-Id`     | Your Cloudflare Access Client ID                                  |
| `CF-Access-Client-Secret` | Your Cloudflare Access Client Secret                              |
| `Authorization`           | Your application API token in the format: `Bearer YOUR_API_TOKEN` |

## How to Obtain Credentials

### Cloudflare Service Token

To obtain a Cloudflare service token:

1. Contact your system administrator to request access to the API
2. You will be provided with a Client ID and Client Secret
3. These credentials are specific to your service or application and should be kept secure

### Application API Token

To obtain an application API token:

1. Log in to the application at `https://laravel.jaxonville.com`
2. Navigate to your profile and select "API Tokens"
3. Click "Create New Token"
4. Select the appropriate scopes for your use case
5. Copy the generated token (it will only be shown once)

## Example API Requests

### Using cURL

```bash
curl -X GET "https://api-laravel.jaxonville.com/example/data" \
  -H "CF-Access-Client-Id: YOUR_CF_CLIENT_ID" \
  -H "CF-Access-Client-Secret: YOUR_CF_CLIENT_SECRET" \
  -H "Authorization: Bearer YOUR_API_TOKEN"
```

### Using JavaScript/Fetch

```javascript
fetch('https://api-laravel.jaxonville.com/example/data', {
  method: 'GET',
  headers: {
    'CF-Access-Client-Id': 'YOUR_CF_CLIENT_ID',
    'CF-Access-Client-Secret': 'YOUR_CF_CLIENT_SECRET',
    'Authorization': 'Bearer YOUR_API_TOKEN'
  }
})
.then(response => response.json())
.then(data => console.log(data))
.catch(error => console.error('Error:', error));
```

### Using PHP/Guzzle

```php
$client = new \GuzzleHttp\Client();
$response = $client->request('GET', 'https://api-laravel.jaxonville.com/example/data', [
    'headers' => [
        'CF-Access-Client-Id' => 'YOUR_CF_CLIENT_ID',
        'CF-Access-Client-Secret' => 'YOUR_CF_CLIENT_SECRET',
        'Authorization' => 'Bearer YOUR_API_TOKEN',
    ]
]);
$data = json_decode($response->getBody(), true);
```

## Rate Limiting

The API is rate-limited to **60 requests per minute** per authenticated user or IP address. If you exceed this limit, you will receive a 429 Too Many Requests response.

The rate limit is implemented using Laravel's throttle middleware with the 'api' rate limiter defined in `App\Providers\AppServiceProvider`.

## Error Handling

### Common Error Codes

| Status Code | Description       | Possible Cause                                                         |
|-------------|-------------------|------------------------------------------------------------------------|
| 401         | Unauthorized      | Invalid or missing API token                                           |
| 403         | Forbidden         | Invalid or missing Cloudflare credentials, or insufficient permissions |
| 429         | Too Many Requests | Rate limit exceeded                                                    |

### Error Response Format

```json
{
  "message": "Error message description",
  "error_code": "specific_error_code",
  "documentation_url": "https://laravel.jaxonville.com/docs/errors#specific_error_code"
}
```

## Security Best Practices

1. **Store Credentials Securely**: Never hardcode credentials in your application code or commit them to version control
2. **Use Environment Variables**: Store your credentials as environment variables
3. **Rotate Tokens Regularly**: Periodically rotate your API tokens and Cloudflare service tokens
4. **Limit Token Scopes**: Only request the minimum scopes necessary for your application
5. **Monitor Usage**: Regularly audit your API usage to detect any unauthorized access

## Troubleshooting

If you encounter issues accessing the API:

1. Verify that your Cloudflare credentials are correct
2. Check that your API token is valid and has the necessary scopes
3. Ensure you're not exceeding the rate limit
4. Confirm that all required headers are included in your request
5. Check the response body for detailed error messages

## Related Documentation

- [API Token Scopes Documentation](API%20Token%20Scopes.md) - Information about available API token scopes
- [Route Protection Documentation](Route%20Protection.md) - Information about route protection, including rate limiting
