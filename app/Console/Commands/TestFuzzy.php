<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use FuzzyWuzzy\Fuzz;
use FuzzyWuzzy\Process;

#[Signature('app:test-fuzzy')]
#[Description('Fuzzy algoritm test')]
class TestFuzzy extends Command
{

    private function matchScore($str1, $str2, Fuzz $fuzz): float
    {
        // tokenSetRatio base
        $setRatio = $fuzz->tokenSetRatio($str1, $str2);

        // Coeficiente de Jaccard
        $tokens1 = array_unique(explode(' ', $str1));
        $tokens2 = array_unique(explode(' ', $str2));
        $intersect = array_intersect($tokens1, $tokens2);
        $union = array_unique(array_merge($tokens1, $tokens2));
        $jaccard = count($union) > 0 ? count($intersect) / count($union) : 0;

        // Puntuación híbrida: combina ambos. Cuanto más se parezcan los conjuntos,
        // más nos fiamos del tokenSetRatio. Ponderación ajustable.
        // jaccard en porcentaje

        return $setRatio * 0.6 + $jaccard * 100 * 0.4;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $fuzz = new Fuzz();
        $strings = ['Salsa Passata Tomate Salsati x 340g.', 'Passata de tomate Salsati en frasco 340 grs'];
        $rules = ['x', 'con', 'grs', 'gr', 'g', 'gr.', 'g.', 'x.', 'de', 'y', 'grs.', 'grm', 'grm.', 'lt', 'lts', 'cc', 'lt.', 'lts.', 'cc.', 'l', 'l.', 'k', 'k.', 'kg', 'kg.', 'ud', 'ud.', 'un', 'un.', 'u', 'u.', 'xkg', 'xkg.' ];
        $measures = ['g', 'kg', 'lt', 'lts', 'gr.', 'ud', 'un', 'cc', 'l', 'k', 'kg.', 'lt.', 'lts.', 'grm', 'grm.', 'cc.', 'l.', 'k.', 'gr', 'xkg', 'xkg.'];
        $isFlour = ['00', '000', '0000'];
        $cleanedStrings = [];

        foreach ($strings as $index => $string) {
            $string = str_replace('-', ' ', $string);
            $string = explode(' ', $string);
            $str = collect($string)->map(function ($word) use ($rules) {

                $word = strtolower($word);
                $word = transliterator_transliterate('Latin-ASCII', $word);

                // extrae el número en caso de que exista
                preg_match('/\d+/', $word, $matches);
                if ($matches) $word = $matches[0];

                // si la palabra está dentro del array de reglas
                if (in_array($word, $rules)) {
                    return '';
                }

                return $word;
            });

            $cleanedStrings[$index] = $str->filter()->implode(' ');

        }

        $this->info("String 1: {$cleanedStrings[0]}");
        $this->info("String 2: {$cleanedStrings[1]}");
        $score = $this->matchScore($cleanedStrings[0], $cleanedStrings[1], $fuzz);
        $this->info("Puntuación híbrida: {$score}%");

    }
}
