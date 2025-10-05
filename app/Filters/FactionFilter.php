<?php namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class FactionFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $user = auth()->user();

        // If logged in but no faction chosen
        if ($user && $user->faction_id === null) {

            $path = trim($request->getUri()->getPath(), '/');

            // Only redirect if NOT already on faction selection or auth route
            if (!str_starts_with($path, 'faction') && !str_starts_with($path, 'auth')) {
                return redirect()->to('/faction/select');
            }
        }

        // Allow other users or routes
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No post-processing needed
    }
}
