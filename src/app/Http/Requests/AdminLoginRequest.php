<?php

namespace App\Http\Requests;

use Laravel\Fortify\Http\Requests\LoginRequest as FortifyLoginRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;


class AdminLoginRequest extends FortifyLoginRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function messages()
    {
        return [
            'email.required' => 'メールアドレスを入力してください。',
            'email.email' => 'メールアドレスの形式が正しくありません。',
            'password.required' => 'パスワードを入力してください。',
        ];
    }

    public function authenticate()
    {
        if (! Auth::guard('admin')->attempt($this->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['ログイン情報が登録されていません。'],
            ]);
        }
    }
}
