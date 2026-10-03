<?php

return [
    /*
    | L'agent garde-t-il sa commission sur l'argent des ventes ?
    | true  : montant attendu = montant vendu − commission agent
    | false : l'agent verse tout, le gérant le paie ensuite (module Versements)
    */
    'commission_agent_deduite' => env('ZT_COMMISSION_AGENT_DEDUITE', true),
];
