<?php
/**
 * JobClass - Job Board Web Application
 * Copyright (c) BedigitCom. All Rights Reserved
 *
 * Website: http://www.bedigit.com
 *
 * LICENSE
 * -------
 * This software is furnished under a license and may be used and copied
 * only in accordance with the terms of such license and with the inclusion
 * of the above copyright notice. If you Purchased from Codecanyon,
 * Please read the full License from here - http://codecanyon.net/licenses/standard
 */

namespace App\Http\Requests;

use App\Rules\BetweenRule;
use App\Rules\BlacklistDomainRule;
use App\Rules\BlacklistEmailRule;
use App\Rules\BlacklistTitleRule;
use App\Rules\BlacklistWordRule;
use App\Rules\UsernameIsAllowedRule;
use App\Rules\UsernameIsValidRule;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Routing\Router;
use Illuminate\Config\Repository;

class RegisterRequest extends Request
{
	/**
	 * Get the validation rules that apply to the request.
	 *
	 * @param \Illuminate\Routing\Router $router
	 * @param \Illuminate\Filesystem\Filesystem $files
	 * @param \Illuminate\Config\Repository $config
	 * @return array
	 */
	public function rules(Router $router, Filesystem $files, Repository $config)
	{
		$rules = [
			//'gender_id'  => ['required', 'not_in:0'],
			'name'         => ['required', new BetweenRule(2, 200)],
			'user_type_id' => ['required', 'not_in:0'],
			'country_code' => ['sometimes', 'required', 'not_in:0'],
			'phone'        => ['max:20'],
			'email'        => ['max:100', new BlacklistEmailRule(), new BlacklistDomainRule()],
			'password'     => ['required', 'between:6,60', 'dumbpwd', 'confirmed'],
			'term'         => ['accepted'],
		];
		
		// Email
		if ($this->filled('email')) {
			$rules['email'][] = 'email';
			$rules['email'][] = 'unique:users,email';
		}
		if (isEnabledField('email')) {
			if (isEnabledField('phone') && isEnabledField('email')) {
				$rules['email'][] = 'required_without:phone';
			} else {
				$rules['email'][] = 'required';
			}
		}
		
		// Phone
		if (config('settings.sms.phone_verification') == 1) {
			if ($this->filled('phone')) {
				$countryCode = $this->input('country_code', config('country.code'));
				if ($countryCode == 'UK') {
					$countryCode = 'GB';
				}
				$rules['phone'][] = 'phone:' . $countryCode;
			}
		}
		if (isEnabledField('phone')) {
			if (isEnabledField('phone') && isEnabledField('email')) {
				$rules['phone'][] = 'required_without:email';
			} else {
				$rules['phone'][] = 'required';
			}
		}
		if ($this->filled('phone')) {
			$rules['phone'][] = 'unique:users,phone';
		}
		
		// Username
		if (isEnabledField('username')) {
			if ($this->filled('username')) {
				$rules['username'] = [
					'between:3,100',
					'unique:users,username',
					new UsernameIsValidRule(),
					new UsernameIsAllowedRule($router, $files, $config)
				];
			}
		}
		
		// COMPANY: Check 'resume' is required
		if (config('larapen.core.register.showCompanyFields')) {
			if ($this->input('user_type_id') == 1) {
				$rules['company.name'] = ['required', new BetweenRule(2, 200), new BlacklistTitleRule()];
				$rules['company.description'] = ['required', new BetweenRule(5, 1000), new BlacklistWordRule()];
				
				// Check 'logo' is required
				if ($this->file('logo')) {
					$rules['logo'] = [
						'required',
						'image',
						'mimes:' . getUploadFileTypes('image'),
						'max:' . (int)config('settings.upload.max_file_size', 1000)
					];
				}
			}
		}
		
		// CANDIDATE: Check 'resume' is required
		if (config('larapen.core.register.showResumeFields')) {
			if ($this->input('user_type_id') == 2) {
				$rules['resume.filename'] = [
					'required',
					'mimes:' . getUploadFileTypes('file'),
					'max:' . (int)config('settings.upload.max_file_size', 1000)
				];
			}
		}
		
		// reCAPTCHA
		$rules = $this->recaptchaRules($rules);
		
		return $rules;
	}
	
	/**
	 * @return array
	 */
	public function messages()
	{
		$messages = [];
		
		return $messages;
	}
}
