package com.company.safedocshare

import android.content.Intent
import android.os.Bundle
import android.view.View
import androidx.lifecycle.lifecycleScope
import com.company.safedocshare.api.ApiClient
import com.company.safedocshare.databinding.ActivityLoginBinding
import com.company.safedocshare.models.LoginRequest
import com.company.safedocshare.utils.PrefsManager
import com.company.safedocshare.utils.SecureActivity
import kotlinx.coroutines.launch

class LoginActivity : SecureActivity() {

    private lateinit var binding: ActivityLoginBinding

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        ApiClient.init(PrefsManager.getServerUrl(this))

        // Already have a valid session — go straight to main
        if (PrefsManager.isLoggedIn(this)) {
            startMain()
            return
        }

        binding = ActivityLoginBinding.inflate(layoutInflater)
        setContentView(binding.root)

        // Pre-fill saved details
        binding.etServerUrl.setText(PrefsManager.getServerUrl(this))
        PrefsManager.getUsername(this)?.let { binding.etUsername.setText(it) }
        PrefsManager.getSavedPassword(this)?.let { binding.etPassword.setText(it) }

        binding.btnLogin.setOnClickListener { attemptLogin() }

        binding.etPassword.setOnEditorActionListener { _, _, _ ->
            attemptLogin()
            true
        }

        // Auto-login if all credentials are saved
        if (PrefsManager.hasSavedCredentials(this)) {
            attemptLogin()
        }
    }

    private fun attemptLogin() {
        val serverUrl = binding.etServerUrl.text?.toString()?.trim() ?: ""
        val username  = binding.etUsername.text?.toString()?.trim() ?: ""
        val password  = binding.etPassword.text?.toString() ?: ""

        if (serverUrl.isEmpty()) {
            showError(getString(R.string.error_server_empty))
            return
        }
        if (username.isEmpty() || password.isEmpty()) {
            showError("Please enter username and password.")
            return
        }

        ApiClient.init(serverUrl)
        setLoading(true)
        hideError()

        lifecycleScope.launch {
            try {
                val response = ApiClient.service.login(LoginRequest(username, password))
                if (response.isSuccessful && response.body()?.success == true) {
                    val data = response.body()!!.data!!
                    PrefsManager.saveCredentials(this@LoginActivity, serverUrl, username, password)
                    PrefsManager.saveSession(this@LoginActivity, data.token, data.username)
                    startMain()
                } else {
                    val msg = response.body()?.message ?: getString(R.string.error_login)
                    showError(msg)
                    setLoading(false)
                }
            } catch (e: Exception) {
                showError(getString(R.string.error_network))
                setLoading(false)
            }
        }
    }

    private fun startMain() {
        startActivity(Intent(this, PdfListActivity::class.java))
        finish()
    }

    private fun setLoading(loading: Boolean) {
        binding.progressBar.visibility = if (loading) View.VISIBLE else View.GONE
        binding.btnLogin.isEnabled = !loading
    }

    private fun showError(msg: String) {
        binding.tvError.text = msg
        binding.tvError.visibility = View.VISIBLE
    }

    private fun hideError() {
        binding.tvError.visibility = View.GONE
    }
}
