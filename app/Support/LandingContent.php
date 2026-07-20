<?php

namespace App\Support;

/**
 * Static editorial copy of the landing (Constitución, FAQ).
 *
 * Lives in PHP rather than the DB because it is brand storytelling, not
 * business data: it changes with a deploy, not from the admin panel. Kept out
 * of the Blade so the markup stays a loop instead of nine copy-pasted blocks.
 * If the brand ever needs to edit this without a deploy, it moves to the DB.
 */
class LandingContent
{
    /**
     * Articles of the "Constitución de la República".
     *
     * @return list<array{number: string, title: string, body: string}>
     */
    public static function constitution(): array
    {
        return [
            [
                'number' => 'Artículo 1',
                'title' => 'Derecho a la desconexión',
                'body' => 'Toda persona tiene derecho a apagar el mundo por un momento. A silenciar las notificaciones, alejarse de las pantallas y no estar disponible todo el tiempo.',
            ],
            [
                'number' => 'Artículo 2',
                'title' => 'Derecho a pausar sin culpa',
                'body' => 'Toda persona tiene derecho a detenerse. A descansar sin justificarlo. La pausa no es un privilegio: es una necesidad humana.',
            ],
            [
                'number' => 'Artículo 3',
                'title' => 'Derecho a habitar el presente',
                'body' => 'Toda persona tiene derecho a estar donde está. Sin vivir atrapada en lo que ya pasó ni anticipar lo que vendrá. La vida ocurre ahora.',
            ],
            [
                'number' => 'Artículo 4',
                'title' => 'Derecho al silencio',
                'body' => 'Toda persona tiene derecho a encontrar espacios sin ruido. A escuchar su respiración y convivir con la quietud. En el silencio también existen respuestas.',
            ],
            [
                'number' => 'Artículo 5',
                'title' => 'Derecho a expresar lo que siente',
                'body' => 'A llorar, reír, cantar, bailar. Las emociones no deben reprimirse; deben atravesarse.',
            ],
        ];
    }

    /** Closing statement of the Constitución, shown as the final card. */
    public static function finalProvision(): string
    {
        return 'La paz no será impuesta. Será practicada. La verdadera independencia consiste en recuperar el gobierno de uno mismo.';
    }

    /**
     * Frequently asked questions, grouped by theme (PDF págs. 7–9).
     *
     * @return list<array{group: string, items: list<array{q: string, a: string}>}>
     */
    public static function faqs(): array
    {
        return [
            [
                'group' => 'Reservas y asistencia',
                'items' => [
                    [
                        'q' => '¿Cómo reservo una práctica?',
                        'a' => 'Desde tu perfil, en el calendario semanal. Elegís el día, la práctica y confirmás: se descuenta una práctica de tu pase en el momento de reservar.',
                    ],
                    [
                        'q' => '¿Necesito reservar o puedo venir directamente?',
                        'a' => 'Siempre conviene reservar. Los cupos son limitados por sala y la reserva es lo único que te asegura el lugar.',
                    ],
                    [
                        'q' => '¿Qué pasa si llego tarde?',
                        'a' => 'Te pedimos llegar 10 minutos antes. Una vez que la práctica empezó, entrar interrumpe al grupo, así que puede que no sea posible sumarte.',
                    ],
                ],
            ],
            [
                'group' => 'Cancelaciones',
                'items' => [
                    [
                        'q' => '¿Hasta cuándo puedo cancelar una práctica grupal?',
                        'a' => 'Hasta 1 hora antes del inicio. Si cancelás a tiempo, la práctica vuelve a tu pase y la podés usar en otro momento. Después de ese plazo, se consume.',
                    ],
                    [
                        'q' => '¿Y una sesión individual?',
                        'a' => 'Las sesiones individuales se reservan con un profesional que aparta ese horario para vos. Si cancelás con menos de 24 horas, se cobra el 50 % de la sesión.',
                    ],
                ],
            ],
            [
                'group' => 'Membresías y pases',
                'items' => [
                    [
                        'q' => '¿Las prácticas de mi pase vencen?',
                        'a' => 'Sí. Cada pase tiene una vigencia desde que se activa (los mensuales, 30 días). Las prácticas que no uses dentro de ese período no se acumulan para el mes siguiente.',
                    ],
                    [
                        'q' => '¿Puedo cambiar de pase?',
                        'a' => 'Sí, cuando quieras. Escribinos y lo ajustamos desde recepción según lo que estés usando.',
                    ],
                    [
                        'q' => '¿Cómo veo cuántas prácticas me quedan?',
                        'a' => 'En tu perfil. Ahí ves tu pase activo, su vencimiento y cuántas prácticas usaste y te quedan.',
                    ],
                ],
            ],
            [
                'group' => 'Primeras clases',
                'items' => [
                    [
                        'q' => 'Nunca hice yoga, ¿puedo empezar igual?',
                        'a' => 'Sí. No hace falta experiencia previa ni flexibilidad. Contale al facilitador que es tu primera vez y va a acompañarte en las variantes.',
                    ],
                    [
                        'q' => '¿Qué llevo?',
                        'a' => 'Ropa cómoda y ganas. Mat, apoyos y todo lo necesario están en el espacio.',
                    ],
                    [
                        'q' => '¿Tengo una clase de prueba?',
                        'a' => 'Sí, la primera práctica grupal es sin costo. Incluye conocer a los facilitadores y un recorrido por el espacio.',
                    ],
                ],
            ],
            [
                'group' => 'Sobre las prácticas',
                'items' => [
                    [
                        'q' => '¿Qué es una terapia de sonido?',
                        'a' => 'Una práctica en la que te recostás y te dejás atravesar por el sonido de cuencos e instrumentos. No hay nada que hacer: el cuerpo entra en un descanso profundo por sí solo.',
                    ],
                    [
                        'q' => '¿Qué son los acompañamientos individuales?',
                        'a' => 'Sesiones uno a uno con un profesional —reiki, tarot, diseño humano, entre otras— para trabajar algo puntual a tu ritmo, con un horario reservado para vos.',
                    ],
                    [
                        'q' => '¿Puedo combinar disciplinas?',
                        'a' => 'Es la idea. Tu pase te da acceso a todas las disciplinas grupales: podés hacer yoga un día y sound healing al siguiente.',
                    ],
                ],
            ],
        ];
    }
}
