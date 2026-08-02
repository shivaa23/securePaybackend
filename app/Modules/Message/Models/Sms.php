<?php
namespace App\Modules\Message\Models;
use Illuminate\Database\Eloquent\Model;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use App\Modules\AuditLog\Models\AuditLog;
class Sms extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        //
    ];
	
	public static function send($data,$user){
	    $message = urlencode($data['message']);

        $url = "https://sms.mobirouteteleservices.com/domestic/sendsms/bulksms_v2.php"
                . "?apikey=aml0b2Z23AnRlY2g6UFdHeWJacEg="
                . "&type=TEXT"
                . "&sender=JITOFT"
                . "&entityId="
                . "&templateId={$data['tempid']}"
                . "&mobile={$data['number']}"
                . "&message=".$message;
		$curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => $url,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'GET',
        ));
        
        $response = curl_exec($curl);
        
        curl_close($curl);
        //echo $response;
         AuditLog::create([
                    'user_id'      => $user->id,
                    'role'         => $user->role,
                    'action'       => 'SMS',
                    'service_name' => 'sms',
                    'request_data' => json_encode($user->mobile),
                    'response_data'=> json_encode($response),
                    'ip_address'   => null,
                    'user_agent'   => 'internal',
                    'status'       => 'Success'
                ]);
        return $response;
	}
}
