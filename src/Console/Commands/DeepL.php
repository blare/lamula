<?php

namespace Blare\Lamula\Console\Commands;

use Illuminate\Console\Command;

class DeepL extends Command
{
    /**
     * El nombre del comando en Artisan.
     */
    protected $signature = 'lamula:deepl {--source-lang= : Set the source language} {--target-lang= : Set the target language} {--lang-dir= : Set the languge directory, usually is resources/lang/} {--show-langs : Show available list of source and target languages.}';

    /**
     * La descripción del comando.
     */
    protected $description = 'Transtales language files using DeepL API';

    /* Valid langs */
    var $valid_source_langs = ['AR' => 'Arabic','BG' => 'Bulgarian','CS' => 'Czech','DA' => 'Danish','DE' => 'German','EL' => 'Greek','EN' => 'English (all English variants)','ES' => 'Spanish','ET' => 'Estonian','FI' => 'Finnish','FR' => 'French','HU' => 'Hungarian','ID' => 'Indonesian','IT' => 'Italian','JA' => 'Japanese','KO' => 'Korean','LT' => 'Lithuanian','LV' => 'Latvian','NB' => 'Norwegian Bokmål','NL' => 'Dutch','PL' => 'Polish','PT' => 'Portuguese (all Portuguese variants)','RO' => 'Romanian','RU' => 'Russian','SK' => 'Slovak','SL' => 'Slovenian','SV' => 'Swedish','TR' => 'Turkish','UK' => 'Ukrainian','ZH' => 'Chinese (all Chinese variants)'];
    var $valid_target_langs = ['AR' => 'Arabic','BG' => 'Bulgarian','CS' => 'Czech','DA' => 'Danish','DE' => 'German','EL' => 'Greek','EN-GB' => 'English (British)','EN-US' => 'English (American)','ES' => 'Spanish','ET' => 'Estonian','FI' => 'Finnish','FR' => 'French','HU' => 'Hungarian','ID' => 'Indonesian','IT' => 'Italian','JA' => 'Japanese','KO' => 'Korean','LT' => 'Lithuanian','LV' => 'Latvian','NB' => 'Norwegian Bokmål','NL' => 'Dutch','PL' => 'Polish','PT-BR' => 'Portuguese (Brazilian)','PT-PT' => 'Portuguese (all Portuguese variants excluding Brazilian Portuguese)','RO' => 'Romanian','RU' => 'Russian','SK' => 'Slovak','SL' => 'Slovenian','SV' => 'Swedish','TR' => 'Turkish','UK' => 'Ukrainian','ZH-HANS' => 'Chinese (simplified)','ZH-HANT' => 'Chinese (traditional)'];
    var $lang_dir_default = 'resources/lang/';
    
    /**
     * Ejecuta la funcionalidad del comando.
     */
    public function handle()
    {

        //Check API key exists
        if(!isset($_ENV['LAMULA_DEEPL_API_KEY'])){
            $this->error(' ❌️ There is no LAMULA_DEEPL_API_KEY defined in .env file.');
            die();
        }
        //Check default lang resource directory exists.
        if(!isset($_ENV['LAMULA_RESOURCE_DIR'])){
            $this->error(' ❌️ There is no LAMULA_RESOURCE_DIR defined in .env file.');
            die();
        }
        $this->lang_dir_default = $_ENV['LAMULA_RESOURCE_DIR'];


        //show help about source and target languages.
        if($this->option('show-langs')){
            $this->showListOfAvailableLangs();
        }

        //Get the source language via option --source-lang=es
        $source_lang = strtolower($this->option('source-lang'));
        $this->isValidSourceLang($source_lang);
        //Get the target language via option --target-lang=en
        $target_lang = strtolower($this->option('target-lang'));
        $this->isValidTargetLang($target_lang);

        //Scan directory with source texts
        $lang_dir = $this->option('lang-dir');
        $files = $this->getSourceFiles($lang_dir, $source_lang);

        foreach($files as $file){
            $dest_file = $this->getDestFile($file, $source_lang, $target_lang);

            if(file_exists($dest_file)){
                $backup_original_dest = $dest_file.'_'.time();
                rename($dest_file, $backup_original_dest);
                $this->info(' ℹ️ The file '.$dest_file.' exists. It has been moved to '.$backup_original_dest.'.');
            }

            // $this->info($dest_file);
            //Read original file
            $content = include($file);
            $output_arr = [];

            $total_texts = count($content);
            $counter = 0;

            foreach($content as $key => $text){
                $counter++;
                $process = number_format((($counter/$total_texts)*100),0);
                $target_text = $this->translateDeepL($text, $source_lang, $target_lang, $process);
                $output_arr[$key] = $target_text;
            }
            $success = $this->createLanguageFile($dest_file, $output_arr);

            if(!$success){
                $this->info(' ❌️ The file '.$dest_file.' has not been written.');
            }else{
                $this->info(' ✅️ The file '.$dest_file.' is ready.');
            }
        }
        
        $this->info('-- end of process --');
    }

    private function getDestFile($file, $source_lang, $target_lang){
        $source_lang = $source_lang;
        $target_lang = $target_lang;

        $exploded = explode(DIRECTORY_SEPARATOR, $file);
        $dest_array = [];
        foreach($exploded as $segment){
            if($segment == $source_lang){
                $segment = $target_lang;
            }
            $dest_array[] = $segment;
        }

        return implode(DIRECTORY_SEPARATOR, $dest_array);
    }

    private function getSourceFiles($lang_dir, $source_lang){
        $source_lang = $source_lang;
        if(is_null($lang_dir)) $lang_dir = $this->lang_dir_default;

        $source_dir = getcwd().'/'.$lang_dir.$source_lang.'/';

        $this->info(' 🔵️ Checking files from '.$source_dir);
        
        $files = glob($source_dir.'*.php');
        foreach($files as $file){
            $this->info('    - found '.$file);
        }

        return $files;
    }

    private function showListOfAvailableLangs(){
        $this->info("These are the available languages\n");
        $this->info('  Source langs');
        $this->info('  ------------');
        foreach($this->valid_source_langs as $key => $val){
            $this->info('    '.$key.' - '.$val);
        }

        $this->info("");
        $this->info('  Target langs');
        $this->info('  ------------');
        foreach($this->valid_target_langs as $key => $val){
            $this->info('    '.$key.' - '.$val);
        }
        
        $random_source = array_rand($this->valid_source_langs);
        $random_target = array_rand($this->valid_target_langs);

        $this->info("\n Example:\n    --source-lang=".$random_source." --target-lang=".$random_target."\n");
        die();
    }

    private function isValidSourceLang($source_lang=null){
        $source_lang = strtoupper($source_lang);

        //check if source_lang is valid
        if(!isset($this->valid_source_langs[$source_lang])){
            $this->error('You selected invalid source language.');
            $this->error('Please specify --source-lang option.');
            $this->error('Use --show-langs for detailed information.');
            die();
        }

        $this->info(' ✅️ Source lang is '.$this->valid_source_langs[$source_lang]);
        //Return the target language
        return true;
    }

    private function isValidTargetLang($target_lang=null){
        $target_lang = strtoupper($target_lang);

        $error_message = null;

        //specific languages exceptions
        if($target_lang=='EN'){
            $error_message = 'Please, use «EN-GB» for British English or «EN-US» for American English.';
        }
        if($target_lang=='PT'){
            $error_message = 'Please, use «PT-BR» for Brazilian Portuguese or «PT-PT» for other Portuguese variants.';
        }
        if($target_lang=='ZH'){
            $error_message = 'Please, use «ZH-HANS» for simplified Chinese or «ZH-HANT» for traditional Chinese.';
        }

        //Return error if proceeds
        if(!is_null($error_message)){
            $this->info(' 🟠️ '.$error_message);
            die();
        }

        //check if target_lang is valid
        if(!isset($this->valid_target_langs[$target_lang])){
            $this->error('You selected invalid target language.');
            $this->error('Please specify --target-lang option.');
            $this->error('Use --show-langs for detailed information.');
            die();

        }

        //Return the target language
        $this->info(' ✅️ Target lang is '.$this->valid_target_langs[$target_lang]);
        return true;
    }

    // This function calls DeepL translation API.
    private function translateDeepL($text, $source_lang, $target_lang, $process=null) {

        $endpoint = 'https://api-free.deepl.com/v2/translate'; // Para cuentas gratuitas.
        // Si usas una cuenta premium, usa: 'https://api.deepl.com/v2/translate'.

        $postData = [
            'auth_key' => $_ENV['LAMULA_DEEPL_API_KEY'],
            'text' => $text,
            'target_lang' => $target_lang,
            'source_lang' => $source_lang
        ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $this->error('Request error: ' . curl_error($ch));
            curl_close($ch);
            return null;
        }

        curl_close($ch);

        $decodedResponse = json_decode($response, true);

        if (isset($decodedResponse['translations'][0]['text'])) {
            $translation = $decodedResponse['translations'][0]['text'];
            $this->info(str_pad($process.'%',6)."$source_lang: $text\n      $target_lang: $translation");
            return $translation;
        } else {
            $this->error('API response error: ' . $response);
            die();
        }
    }

    // Escape quotes for texts
    private function escape_quotes($array) {
        return $array;
        foreach ($array as $key => $value) {
            if (is_string($value)) {
                // Escapar las comillas dobles dentro de las cadenas
                $array[$key] = addslashes($value);
            }
        }
        return $array;
    }

    // Función para crear el archivo de idioma
    private function createLanguageFile($filePath, $translations) {
        // Escapar las comillas dobles en las traducciones
        $escapedTranslations = $this->escape_quotes($translations);

        // Verifica si el directorio existe, si no, crea el directorio
        if (!file_exists(dirname($filePath))) {
            mkdir(dirname($filePath), 0777, true);
        }

        // Abre el archivo en modo escritura (creará el archivo si no existe)
        $file = fopen($filePath, 'w');

        // Verifica si se abrió correctamente el archivo
        if ($file) {
            // Escribe el contenido PHP con el array de traducciones en el archivo
            $content = "<?php\n\nreturn " . var_export($escapedTranslations, true) . ";\n";

            // Escribe el contenido en el archivo
            fwrite($file, $content);

            // Cierra el archivo después de escribir
            fclose($file);

            return true;
        } else {
            return false;
        }
    }
}