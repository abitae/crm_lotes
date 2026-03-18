<?php

namespace App\Http\Controllers\Inmopro;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProcessDiagramsController extends Controller
{
    /**
     * Muestra la vista con los diagramas Mermaid del sistema (GRAFICO_PROCESOS_SISTEMA.md).
     *
     * @return array<int, array{title: string, code: string}>
     */
    private function extractDiagramsFromMarkdown(string $path): array
    {
        if (! is_file($path)) {
            return [];
        }

        $content = file_get_contents($path);
        $diagrams = [];
        $pattern = '/^##\s+(.+)$\s*```mermaid\s*\n([\s\S]*?)```/m';

        if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $diagrams[] = [
                    'title' => trim($match[1]),
                    'code' => trim($match[2]),
                ];
            }
        }

        return $diagrams;
    }

    public function __invoke(Request $request): Response
    {
        $path = base_path('docs/GRAFICO_PROCESOS_SISTEMA.md');
        $diagrams = $this->extractDiagramsFromMarkdown($path);

        return Inertia::render('inmopro/process-diagrams', [
            'diagrams' => $diagrams,
        ]);
    }
}
