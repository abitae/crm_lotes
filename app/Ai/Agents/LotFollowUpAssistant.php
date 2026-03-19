<?php

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\UseCheapestModel;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

#[UseCheapestModel]
class LotFollowUpAssistant implements Agent
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<'TXT'
Eres un asistente para asesores inmobiliarios en un CRM de lotes (Inmopro).
Generas textos breves y profesionales en español para seguimiento al cliente o próximos pasos comerciales.
No inventes datos que no aparezcan en el contexto. No prometas precios ni condiciones legales concretas.
Devuelve solo el texto sugerido para copiar y pegar, sin listas numeradas largas ni markdown.
TXT;
    }
}
