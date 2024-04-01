<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerificationCodeEmail;
use Illuminate\Http\Request;


class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'verification_code' => ['required', 'string'], // Adicione essa linha para validar o código de verificação
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\Models\User
     */
    protected function create(array $data)
    {

        if ($data['verification_code'] !== session('verification_code')) {
            throw new \Exception('O código de verificação inserido é inválido.');
        }
    
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'verification_code' => $data['verification_code'],
        ]);
    
        // Limpar a sessão do código de verificação após o registro ser concluído
        session()->forget('verification_code');
    
        return $user;
    }

    public function sendVerificationCode(Request $request)
        {
            $request->validate([
                'email' => 'required|email',
            ]);

            $verificationCode = Str::random(9);
            session(['verification_code' => $verificationCode]);
            session(['email_to_verify' => $request->email]);

            // Enviar e-mail de verificação
            Mail::to($request->email)->send(new VerificationCodeEmail($verificationCode));

            return response()->json(['message' => 'Código de verificação enviado com sucesso.']);

        }
}
