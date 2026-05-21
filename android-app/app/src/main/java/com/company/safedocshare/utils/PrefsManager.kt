package com.company.safedocshare.utils

import android.content.Context
import androidx.security.crypto.EncryptedSharedPreferences
import androidx.security.crypto.MasterKey

object PrefsManager {

    private const val PREFS_FILE = "safe_doc_prefs"
    private const val KEY_TOKEN    = "token"
    private const val KEY_USERNAME = "username"

    private fun prefs(ctx: Context) = EncryptedSharedPreferences.create(
        ctx,
        PREFS_FILE,
        MasterKey.Builder(ctx).setKeyScheme(MasterKey.KeyScheme.AES256_GCM).build(),
        EncryptedSharedPreferences.PrefKeyEncryptionScheme.AES256_SIV,
        EncryptedSharedPreferences.PrefValueEncryptionScheme.AES256_GCM
    )

    fun saveSession(ctx: Context, token: String, username: String) {
        prefs(ctx).edit()
            .putString(KEY_TOKEN, token)
            .putString(KEY_USERNAME, username)
            .apply()
    }

    fun getToken(ctx: Context): String? = prefs(ctx).getString(KEY_TOKEN, null)

    fun getUsername(ctx: Context): String? = prefs(ctx).getString(KEY_USERNAME, null)

    fun isLoggedIn(ctx: Context): Boolean = !getToken(ctx).isNullOrBlank()

    fun clearSession(ctx: Context) {
        prefs(ctx).edit().clear().apply()
    }
}
