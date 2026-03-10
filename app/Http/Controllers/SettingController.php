<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        return view('settings.index');
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'name'  => 'nullable|string|max:100',
            'email' => 'nullable|email|max:100',
            'bio'   => 'nullable|string|max:500',
        ]);

        // Store in session (no auth/DB in this app)
        session([
            'user_name'  => $request->name,
            'user_email' => $request->email,
            'user_bio'   => $request->bio,
        ]);

        return redirect()->to(route('settings.index') . '#profile')
            ->with('success', 'Profil berhasil disimpan.');
    }

    public function updatePreferences(Request $request)
    {
        session([
            'pref_currency'  => $request->currency ?? 'IDR',
            'pref_language'  => $request->language ?? 'en',
            'pref_theme'     => $request->theme ?? 'dark',
            'pref_date_fmt'  => $request->date_format ?? 'd/m/Y',
        ]);

        return redirect()->to(route('settings.index') . '#preferences')
            ->with('success', 'Preferensi berhasil disimpan.');
    }

    public function updateSecurity(Request $request)
    {
        $request->validate([
            'pin'         => 'nullable|numeric|digits_between:4,6',
            'pin_confirm' => 'nullable|same:pin',
        ]);

        if ($request->pin) {
            session(['app_pin' => bcrypt($request->pin)]);
        }

        session(['pref_auto_lock' => $request->has('auto_lock')]);

        return redirect()->to(route('settings.index') . '#security')
            ->with('success', 'Pengaturan keamanan berhasil disimpan.');
    }

    public function updateNotifications(Request $request)
    {
        session([
            'notif_price_alert'    => $request->has('notif_price_alert'),
            'notif_debt_reminder'  => $request->has('notif_debt_reminder'),
            'notif_budget_warning' => $request->has('notif_budget_warning'),
            'notif_daily_summary'  => $request->has('notif_daily_summary'),
        ]);

        return redirect()->to(route('settings.index') . '#notifications')
            ->with('success', 'Pengaturan notifikasi berhasil disimpan.');
    }

    public function syncDb()
    {
        $scriptPath = base_path('scripts\sync_db.ps1');
        
        // Execute the powershell script
        // Note: this will block the request. Doing it synchronously here as it's a local admin tool.
        $output = [];
        $returnVar = 0;
        
        exec("powershell.exe -ExecutionPolicy Bypass -File \"$scriptPath\" 2>&1", $output, $returnVar);

        $logPath = storage_path('logs\sync_db_log.txt');
        $logContent = file_exists($logPath) ? file_get_contents($logPath) : implode("\n", $output);

        if ($returnVar === 0) {
            return redirect()->to(route('settings.index') . '#integrations')
                ->with('success', 'Sync Database Production ke Local selesai dengan sukses!');
        } else {
            return redirect()->to(route('settings.index') . '#integrations')
                ->withErrors(['sync' => 'Gagal melakukan sinkronisasi database. Cek log untuk detail.'])->with('sync_log', $logContent);
        }
    }
}
