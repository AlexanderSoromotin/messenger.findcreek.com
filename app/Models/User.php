<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as AuthenticatableUser;
use Illuminate\Notifications\Notifiable;

use Illuminate\Support\Facades\Http;
use Illuminate\Database\Eloquent\Model;

class User extends AuthenticatableUser implements Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = "users";
//    protected $fillable = ["id", "privacy"];
    protected $guarded = [];

    public  function chats()
    {
        // Проверяем, есть ли записи в таблице user_chats для текущего пользователя
        if ($this->user_chats()) {
            return $this->belongsToMany(Chat::class, 'user_chats', 'user_id', 'chat_id');
        } else {
            // Если нет записей, возвращаем пустую коллекцию
            return collect();
        }
    }

    public function user_chats()
    {
        return $this->hasMany(UserChat::class, 'user_id');
    }

    public static function getRemoteUserData($token, $fields = "")
    {
        $url = env("ID_SERVICE_URL") . "account.getInfo/";
        $response = Http::get($url, [
            "token" => $token,
            "fields" => $fields
        ]);

        if ($response->successful()) {
            $json = $response->json();
            if (!empty($json["error"])) {
                return "invalid token";
            }

            User::firstOrCreate(
                ["id" => $json["response"]["id"]],
                ["id" => $json["response"]["id"]]
            );

            return $json;
        }

        // Если запрос не успешен, верните null или выбросьте исключение, в зависимости от вашего сценария
        return null;
    }

    /**
     * Get the name of the unique identifier for the user.
     *
     * @return string
     */
    public function getAuthIdentifierName()
    {
        return 'id';
    }

    /**
     * Get the unique identifier for the user.
     *
     * @return mixed
     */
    public function getAuthIdentifier()
    {
        return $this->id;
    }

    /**
     * Get the password for the user.
     *
     * @return null
     */
    public function getAuthPassword()
    {
        return null;
    }

    /**
     * Get the token value for the "remember me" session.
     *
     * @return null
     */
    public function getRememberToken()
    {
        return null;
    }

    /**
     * Set the token value for the "remember me" session.
     *
     * @param  string|null  $value
     * @return void
     */
    public function setRememberToken($value)
    {
        // do nothing
    }

    /**
     * Get the column name for the "remember me" token.
     *
     * @return null
     */
    public function getRememberTokenName()
    {
        return null;
    }
}
