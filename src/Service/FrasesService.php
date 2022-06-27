<?php

namespace App\Service;

class FrasesService {

    public function getFraseAleatoria(): string {

        $frases = [
            "El pasado puede doler pero, tal y como yo lo veo, puedes: o huir de él o aprender",
            "La vida no es un deporte de mirones: si pasas el tiempo observando, verás tu vida pasar y tú te quedarás atrás",
            "La flor que florece en la adversidad es la más rara y hermosa de todas",
            "Algunas veces el camino correcto no es el más fácil",
            "Yo no estoy loco, mi realidad es diferente a la tuya",
            "Eres más valiente de lo que crees, más fuerte de lo que pareces y más inteligente de lo que piensas",
            "Si te centras en lo que dejas atrás, no podrás ver lo que tienes delante",
            "Un héroe verdadero no lo es por el tamaño de sus músculos, sino por el de su corazón",
            "Debo dejar de pretender ser algo que no soy",
            "Hasta el infinito y más allá",
            "Gracias por esta aventura, ¡ahora te toca a ti vivir una nueva!",
            "Cuando amas a alguien, permanece dentro de tu corazón para siempre",
            "Si hay una cosa que nadie ha podido comprar con dinero, ésa es el movimiento de la cola de un perro",
            "Hay personas por las que vale la pena derretirse",
            "Siempre deja que tu conciencia sea tu guía",
            "Sigue nadando",
        ];

        return $frases[array_rand($frases)];
    }
}