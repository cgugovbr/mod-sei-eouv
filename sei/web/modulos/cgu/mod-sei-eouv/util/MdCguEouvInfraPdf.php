<?php

class MdCguEouvInfraPdf extends InfraEtiquetasPDF
{
    public function __construct()
    {
        $this->_Metric_Doc = 'pt';

        parent::__construct([
            'name' => 'relatorio-cgu-eouv',
            'paper-size' => 'A4',
            'metric' => 'pt',
            'marginLeft' => 0,
            'marginTop' => 0,
            'NX' => 1,
            'NY' => 1,
            'SpaceX' => 0,
            'SpaceY' => 0,
            'width' => 595.28,
            'height' => 841.89,
            'font-size' => 10,
            'orientacao' => 'V',
            'style' => '',
        ], 'pt');

        $this->SetMargins(28.35, 28.35);
        $this->SetAutoPageBreak(true, 56.70);
    }
}