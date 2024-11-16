<?php

namespace App\Http\Middleware;

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use ReflectionMethod;

class LogJsonResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Define se o JSON será formatado no log
        $formatJson = true;

        // Processa a resposta da requisição
        $response = $next($request);

        // Verifica se o ambiente é 'local' usando a configuração e se a resposta é do tipo JSON
        if (config('app.env') === 'local') {

            $route = $request->route();
            $action = $route ? $route->getActionName() : null;
            $controllerClass = $action ? explode('@', $action)[0] : 'N/A';
            $controllerMethod = $action ? explode('@', $action)[1] ?? 'N/A' : 'N/A';

            // Inicializa as linhas do método como 'N/A' caso não sejam encontradas
            $startLine = 'N/A';
            $endLine = 'N/A';
            $vscodeLink = 'N/A';

            // Usa reflexão para obter as linhas do método e link do VS Code se a classe e método existirem
            if ($controllerClass !== 'N/A' && $controllerMethod !== 'N/A') {
                try {
                    $reflection = new ReflectionMethod($controllerClass, $controllerMethod);
                    $startLine = $reflection->getStartLine();
                    $endLine = $reflection->getEndLine();
                    $controllerPath = $reflection->getFileName();

                    // Formata o link para abrir no VS Code
                    $vscodeLink = "file:///$controllerPath:$startLine";
                    $vscodeLink = str_replace("\\", "/", $vscodeLink);
                } catch (\ReflectionException $e) {
                    // Se ocorrer um erro na reflexão, mantemos 'N/A' como linha
                }
            }

            // Construindo a string do log da Request
            $requestData = "URL: " . $request->fullUrl() . "\n";
            $requestData .= "Controller: " . $controllerClass . "\n";
            $requestData .= "Method: " . $controllerMethod . " (Lines: $startLine - $endLine)\n";
            $requestData .= "VS Code Link: " . $vscodeLink . "\n";
            $requestData .= "HTTP Method: " . $request->method() . "\n";
            $requestData .= "Headers:\n";
            $requestData .= "Body:\n";
            foreach ($request->all() as $key => $value) {
                $requestData .= "  - " . $key . ": " . (is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value) . "\n";
            }

            // Construindo a string do log da Response
            $responseData = "Status: " . $response->getStatusCode() . "\n"; // Alterado para getStatusCode()
            $responseContent = json_decode($response->getContent(), true);
            if (is_array($responseContent)) {
                $responseData .= "Content:\n";
                foreach ($responseContent as $key => $value) {
                    $responseData .= "  - " . $key . ": " . (is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value) . "\n";
                }
            } else {
                $responseData .= "Content: " . $response->getContent() . "\n";
            }

            // Formata o log final
            $formattedLog = "\n=== Request ===\n" . $requestData .
                "\n=== Response ===\n" . $responseData .
                "\n====================\n";

            // Usa error_log para registrar a mensagem no log de erros do PHP
            error_log($formattedLog);
        }

        return $response;
    }
}
