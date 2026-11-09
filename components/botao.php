<?php

class botao 
{
    protected $titulo;
    protected $casa;
    protected $empate;
    protected $fora;
    protected $outras;
    protected $data;
    protected $jogos;





    public function __construct($titulo, $casa ,$empate, $fora, $outras,$data,$jogos)
    {
        $this->titulo  =   $titulo;
        $this->casa  =   $casa;
        $this->empate  =   $empate;
        $this->fora  =   $fora;
        $this->outras  =   $outras;
        $this->data  =   $data;
        $this->jogos  =   $jogos;

        
    }


    public function show()
    {
        $titulo = $this->titulo; 
        $casa = $this->casa;
        $empate = $this->empate;
        $fora = $this->fora; 
        $outras = $this->outras;
        $data = $this->data;
        $jogos = $this->jogos;

    require __DIR__.'';

    }



}