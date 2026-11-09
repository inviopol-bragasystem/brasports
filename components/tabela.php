<?php
$url = file_get_contents('https://apijogos.com/betmma.php');
$mma = json_decode($url);
// echo "<pre>";
// print_r($mma);


// $mma->odd[0]->taxa; 
?>










<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">

    <title>Hello, world!</title>
  </head>
  <body>
   
    
    
    










    <table class="table table-dark table-striped">
            <thead>
            <tr>
                <th scope="col">Data Hora</th>
                <th scope="col">${campeonato.title}</th>
                <th scope="col">CASA</th>
                <th scope="col">EMP</th>
                <th scope="col">FORA</th>
                <th scope="col">OUTRAS COTAÇÕES</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <th scope="row">${jogo.data.split('-').reverse().join('/')}<br /> às ${jogo.hora.replace(/:00$/, '')} </th>
                <th class="text-warning" scope="row"> ${jogo.casa} VS ${jogo.fora}</th>
                <td><button type="button" class="btn btn-primary text-light py-2 px-3" data-toggle="modal" data-target="#exampleModal">$ ${jogo.cotacoes['90'][c.campo]|maskReal}</button>

                    <!-- Modal -->
                    <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            
                    <div class="modal-dialog" role="document">
                        
                        <div class="card text-white bg-dark mb-3" style="max-width: 18rem;">
                        <button type="button" class="close  justify-content-end" aria-label="Close">
                        <span class="text-light" aria-hidden="true">&times;</span>
                        </button> 
                    <div class="card-header"> Boletim de Aposta</div>
                    <div class="card-body">
                        <h5 class="card-title">${v.jogo.casa}</b> x <b>${v.jogo.fora}</h5>
                        <h6 class="card-subtitle mb-2 text-muted">Data Hora: ${v.jogo.data.split('-').reverse().join('/')} às ${v.jogo.hora.replace(/:00$/, '')}</h6>
                        <h6 class="card-subtitle mb-2 text-muted"> ${v.cotacao.sigla}  Taxa: ${v.jogo.cotacoes[v.tempo][v.cotacao.campo]|maskReal}</h6>
                        <ul class="list-group list-group-flush text-white bg-dark mb-3">
                        <li class="list-group-item text-white bg-dark mb-3">Quantidade de Jogos: ${aposta.jogos.length}</li>
                        <li class="list-group-item text-white bg-dark mb-3">Cotação: ${valorCotacao}</li>
                        <li class="list-group-item text-white bg-dark mb-3">Retorno: ${premio}</li>
                        </ul>
            
                        <p class="card-text">Quando a vida te desafiar dobre a aposta</p>
            
                        <div class="input-group mb-3">
                        <div class="input-group-prepend">
                            <span class="input-group-text">R$ </span>
                        </div>
                        <input type="number" v-model="valorAposta" v-bind:min="apostaMinima" v-bind:max="apostaMaxima"
                       step="1.00"
                       class="form-control"/>
            
                        </div>
            
            
            
                        <a href="#" class="btn btn-success px-4"  v-bind:disabled="!apostaValida"
                        v-on:click="finalizarAposta"> {{ user ? 'Concluír' : 'Confirmar' }}</a>
                        <a href="#" class="btn btn-danger px-4"  v-on:click="limpaAposta"> Cancelar </a>
                    </div>
                    </div>
            
                </div></td>
                <td> <button type="button" class="btn btn-primary text-light py-2 px-3" data-toggle="modal" data-target="#exampleModal">$ ${jogo.cotacoes['90'][c.campo]|maskReal}</button>  </td>
                <td> <button type="button" class="btn btn-primary text-light py-2 px-3" data-toggle="modal" data-target="#exampleModal">$ ${jogo.cotacoes['90'][c.campo]|maskReal}</button> </td>
                <td><button type="button" class="btn btn-primary text-light py-2 px-3" data-toggle="modal" data-target="#exampleModal"> ${totalCotacoes(jogo.cotacoes)-getCotacoesPrincipais().length}</button> </td>
            </tr>
            
            </tbody>
        </table>





























    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
  </body>
</html>









