<?php

namespace App\Helpers;

use App\Models\EvidenceFiles;
use App\Models\Modules;
use App\Models\MonitoramentoActividade;
use App\Models\Blacklist;
use App\Models\Supply;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Helper
{

    public static function removerCaracteresEspeciais($texto)
    {
        $textoSemEspeciais = preg_replace('/[^a-zA-Z0-9]/', '', $texto);
        $textoMinusculo = strtolower($textoSemEspeciais);
        return $textoMinusculo;
    }
    public static function get_client_ip()
    {
        $ipaddress = '';
        if (isset($_SERVER['REMOTE_ADDR']))
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        else if (isset($_SERVER['HTTP_CLIENT_IP']))
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else if (isset($_SERVER['HTTP_X_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        else if (isset($_SERVER['HTTP_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        else if (isset($_SERVER['HTTP_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        else
            $ipaddress = 'UNKNOWN';
        return $ipaddress;
    }
    public static function objectJson($data, $assoc = false)
    {
        return json_decode(json_encode($data), $assoc);
    }

    public static function getExtensionFile($file)
    {
        if (!is_null($file)) {
            $extension = $file->getClientOriginalExtension();
            return  $extension;
        }
    }
    public static function returnApi($messages, $status, $data = null, $header = null)
    {
        $response = ['status' => '0', 'message' => 'Erro de validação'];
        $response['status'] = $status;
        $response['message'] = $messages;
        if ($data != null) {
            $response['data'] = $data;
        }
        return response($response, $status)->withHeaders([
            $header
        ]);
    }

    public static function upload($image, string $local, $rand_status = true)
    {
        if (is_file($image)) {
            $extension = $image->getClientOriginalExtension();
            if ($rand_status) {
                $rand = rand(1, 1000000);
                $picture = time() . $rand . '.' . $extension;
            } else {
                $picture = time() . '.' . $extension;
            }

            $destinationPath = public_path() . $local;
            $res = $image->move($destinationPath, $picture);
            if ($res) {
                return [
                    'status' => true,
                    'message' => $picture
                ];
            }
            return [
                'status' => false,
                'message' => "Erro no upload"
            ];
        }
    }


    public static function addYear($year)
    {
        $date_atual = new \DateTime(date('Y-m-d'));
        $day_start = $year;
        $data_expire = $date_atual->modify('+' . $day_start . ' year');
        return $data_expire;
    }

    public static function convertBaseImage($path)
    {
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);

        return $base64;
    }




    

    public static function formatDate($data)
    {
        $date = Carbon::parse($data)->locale('pt_PT');

        return str_replace("De ", "de ", ucwords($date->translatedFormat('d \d\e F \d\e Y, \à\s H:i')));
    }
    public static function formatOutTime($data)
    {
        $date = Carbon::parse($data)->locale('pt_PT');

        return str_replace("De ", "de ", ucwords($date->translatedFormat('d \d\e F \d\e Y')));
    }




    public static function formatarString($string)
    {
        // Normalizar a string para remover acentos
        $string = iconv('UTF-8', 'ASCII//TRANSLIT', $string);
        // Substituir espaços por hífens
        $stringFormatada = preg_replace('/\s+/', '-', $string);
        // Converter para minúsculas
        $stringFormatada = strtolower($stringFormatada);
        // Remover caracteres especiais, mantendo apenas letras, números e hífens
        $stringFormatada = preg_replace('/[^a-z0-9\-áéíóúãõâêîôûàèìòùäëïöüçñ]/', '', $stringFormatada);

        return $stringFormatada;
    }
}
