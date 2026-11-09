<?php

$url  = file_get_contents('https://apijogos.com/betmma.php');
$url = json_decode($url);






?>







<!doctype html>
<html lang="en">

<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
  <link rel="stylesheet" href="style.css">

  <title>Layout</title>
</head>

<body class="bg-light">

  <div class="container-fluid">

    <div class="container my-5">
      <div class="row">
        <div class=" col-4 ">
          <nav class="navbar navbar-dark bg-primary top-fixed py-3 rounded">
            <button class="navbar-toggler  " type="button" data-toggle="collapse" data-target="#navbarsExample01" aria-controls="navbarsExample01" aria-expanded="false" aria-label="Toggle navigation">
              <i class="fa fa-btc" aria-hidden="true"></i>
            </button>
            <div class="navbar-collapse collapse" id="navbarsExample01">
              <h1 class="text-light text-center h4">Modalidades</h1>
              <ul class="navbar-nav mr-auto">
                <li class="nav-item dropright">
                  <a class="nav-link dropdown" href="http://example.com" id="dropdown01" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <img src="icones/futebol.png"> Futebol </a>

                  <div class="dropdown-menu " aria-labelledby="dropdown01">
                    <ul>
                      <li class="dropdown-item " v-for="c in getCampeonatos(null, false)">
                        <a href="javascript:;" v-on:click="setCampeonato(c)" v-bind:class="{ active: findCampeonato == c.id }">
                          ${c.title}
                        </a>
                      </li>

                    </ul>
                  </div>
                </li>


                <li class="nav-item dropright">
                  <a class="nav-link dropdown" href="http://example.com" id="dropdown01" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <img src="icones/tenis.png"> Tenis</a>

                  <div class="dropdown-menu" aria-labelledby="dropdown01">
                    <ul>
                      <li class="dropdown-item " href="#">jogos </li>

                    </ul>
                  </div>
                </li>


                <li class="nav-item dropright">
                  <a class="nav-link dropdown" href="http://example.com" id="dropdown01" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <img src="icones/basquete.png"> Basquetes</a>


                  <div class="dropdown-menu" aria-labelledby="dropdown01">
                    <ul>
                      <li class="dropdown-item " href="#">jogos </li>

                    </ul>
                  </div>
                </li>


                <li class="nav-item dropright">
                  <a class="nav-link dropdown" href="http://example.com" id="dropdown01" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">

                    <img src="icones/hoquei.png"> Hóquei no Gelo</a>

                  <div class="dropdown-menu" aria-labelledby="dropdown01">
                    <ul>
                      <li class="dropdown-item " href="#">jogos </li>

                    </ul>
                  </div>
                </li>


                <li class="nav-item dropright">
                  <a class="nav-link dropdown" href="http://example.com" id="dropdown01" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">

                    <img src="icones/beisebol.png"> Beisebol</a>
                  <div class="dropdown-menu" aria-labelledby="dropdown01">
                    <ul>
                      <li class="dropdown-item " href="#">jogos </li>

                    </ul>
                  </div>
                </li>

                <li class="nav-item dropright">
                  <a class="nav-link dropdown" href="http://example.com" id="dropdown01" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">

                    <img src="icones/fut-americano.png"> Futebol Americano</a>

                  <div class="dropdown-menu" aria-labelledby="dropdown01">
                    <ul>
                      <li class="dropdown-item " href="#">jogos </li>

                    </ul>
                  </div>
                </li>


                <li class="nav-item dropright">
                  <a class="nav-link dropdown" href="http://example.com" id="dropdown01" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <img src="icones/handebol.png"> Handebol</a>
                  <div class="dropdown-menu" aria-labelledby="dropdown01">
                    <ul>
                      <li class="dropdown-item " href="#">jogos </li>

                    </ul>
                  </div>
                </li>


                <li class="nav-item dropright">
                  <a class="nav-link dropdown" href="http://example.com" id="dropdown01" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <img src="icones/tenis.png"> Tênis de Mesa</a>

                  <div class="dropdown-menu" aria-labelledby="dropdown01">
                    <ul>
                      <li class="dropdown-item " href="#">jogos </li>

                    </ul>
                  </div>
                </li>


                <li class="nav-item dropright">
                  <a class="nav-link dropdown" href="http://example.com" id="dropdown01" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <img src="icones/luvas-de-boxe.png"> MMA</a>
                  <div class="dropdown-menu" aria-labelledby="dropdown01">
                    <ul>
                      <li class="dropdown-item " href="#">jogos </li>

                    </ul>
                  </div>
                </li>

                <li class="nav-item dropright">
                  <a class="nav-link dropdown" href="http://example.com" id="dropdown01" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <img src="icones/bola-de-bilhar.png"> Sinuca</a>

                  <div class="dropdown-menu" aria-labelledby="dropdown01">
                    <ul>
                      <li class="dropdown-item " href="#">jogos </li>

                    </ul>
                  </div>
                </li>


                <li class="nav-item dropright">
                  <a class="nav-link dropdown" href="http://example.com" id="dropdown01" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <img src="icones/alvo.png"> Dardos</a>

                  <div class="dropdown-menu" aria-labelledby="dropdown01">
                    <ul>
                      <li class="dropdown-item " href="#">jogos </li>

                    </ul>
                  </div>
                </li>

                <li class="nav-item dropright">
                  <a class="nav-link dropdown" href="http://example.com" id="dropdown01" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <img src="icones/bruxo.png"> League Of Legends</a>


                  <div class="dropdown-menu" aria-labelledby="dropdown01">
                    <ul>
                      <li class="dropdown-item " href="#">jogos </li>

                    </ul>
                  </div>
                </li>

                <li class="nav-item dropright">
                  <a class="nav-link dropdown" href="http://example.com" id="dropdown01" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <img src="icones/pistola.png"> CS GO</a>


                  <div class="dropdown-menu" aria-labelledby="dropdown01">
                    <ul>
                      <li class="dropdown-item " href="#">jogos </li>

                    </ul>
                  </div>
                </li>

                <li class="nav-item dropright">
                  <a class="nav-link dropdown" href="http://example.com" id="dropdown01" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <img src="icones/jogador-de-criquete.png"> Críquete</a>


                  <div class="dropdown-menu" aria-labelledby="dropdown01">
                    <ul>
                      <li class="dropdown-item " href="#">jogos </li>

                    </ul>
                  </div>
                </li>
              </ul>
            </div>

          </nav>
        </div>
        <div class="col-8">

          <div id="carouselExampleIndicators" class="carousel slide" data-ride="carousel">
            <ol class="carousel-indicators">
              <li data-target="#carouselExampleIndicators" data-slide-to="0" class="active"></li>
              <li data-target="#carouselExampleIndicators" data-slide-to="1"></li>
              <li data-target="#carouselExampleIndicators" data-slide-to="2"></li>
            </ol>
            <div class="carousel-inner  rounded">
              <div class="carousel-item active">
                <img class="d-block w-100" src="/imagens/20220809_62f2b5935d7fb.png" alt="First slide">
              </div>
              <div class="carousel-item">
                <img class="d-block w-100" src="/imagens/20220809_62f2b202a9d45.png" alt="Second slide">
              </div>
              <div class="carousel-item">
                <img class="d-block w-100" src="/imagens/20220707_62c7027e42463.jpg" alt="Third slide">
              </div>
            </div>
            <a class="carousel-control-prev" href="#carouselExampleIndicators" role="button" data-slide="prev">
              <span class="carousel-control-prev-icon" aria-hidden="true"></span>
              <span class="sr-only">Previous</span>
            </a>
            <a class="carousel-control-next" href="#carouselExampleIndicators" role="button" data-slide="next">
              <span class="carousel-control-next-icon" aria-hidden="true"></span>
              <span class="sr-only">Next</span>
            </a>
          </div>



        </div>
      </div>

      <!-- <div class="col-4"> </div> -->


      </nav>
    </div>



















    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>



</body>

</html>