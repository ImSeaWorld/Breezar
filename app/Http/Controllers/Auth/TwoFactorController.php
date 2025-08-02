<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use App\Models\ActivityLog;

class TwoFactorController extends Controller
{
    public function show(Request $request)
    {
        return Inertia::render('Auth/TwoFactor', [
            'enabled' => !empty($request->user()->two_factor_secret),
        ]);
    }

    public function enable(Request $request)
    {
        $google2fa = new Google2FA();
        $user = $request->user();
        
        $secret = $google2fa->generateSecretKey();
        
        $user->update([
            'two_factor_secret' => encrypt($secret),
        ]);

        $qrCodeUrl = $google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );

        $writer = new Writer(
            new ImageRenderer(
                new RendererStyle(200),
                new ImagickImageBackEnd()
            )
        );

        $qrCode = base64_encode($writer->writeString($qrCodeUrl));

        ActivityLog::log('2fa_enabled', $user);

        return back()->with([
            'qrCode' => 'data:image/png;base64,' . $qrCode,
            'secret' => $secret,
        ]);
    }

    public function disable(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $google2fa = new Google2FA();
        $user = $request->user();
        $secret = decrypt($user->two_factor_secret);

        if (!$google2fa->verifyKey($secret, $request->code)) {
            return back()->withErrors(['code' => 'Invalid 2FA code']);
        }

        $user->update([
            'two_factor_secret' => null,
        ]);

        ActivityLog::log('2fa_disabled', $user);

        return redirect()->route('2fa.show')->with('success', '2FA has been disabled');
    }

    public function verify(Request $request)
    {
        return Inertia::render('Auth/TwoFactorVerify');
    }

    public function confirm(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $google2fa = new Google2FA();
        $user = $request->user();
        $secret = decrypt($user->two_factor_secret);

        if (!$google2fa->verifyKey($secret, $request->code)) {
            return back()->withErrors(['code' => 'Invalid 2FA code']);
        }

        session(['2fa_verified' => true]);
        
        $intended = session('url.intended', route('dashboard'));
        
        return redirect($intended);
    }
}
