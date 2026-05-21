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

        // Skip login if already authenticated
        if (PrefsManager.isLoggedIn(this)) {
            startMain()
            return
        }

        binding = ActivityLoginBinding.inflate(layoutInflater)
        setContentView(binding.root)

        binding.btnLogin.setOnClickListener { attemptLogin() }

        // Allow IME Done key to trigger login
        binding.etPassword.setOnEditorActionListener { _, _, _ ->
            attemptLogin()
            true
        }
    }

    private fun attemptLogin() {
        val username = binding.etUsername.text?.toString()?.trim() ?: ""
        val password = binding.etPassword.text?.toString() ?: ""

        if (username.isEmpty() || password.isEmpty()) {
            showError("Please enter username and password.")
            return
        }

        setLoading(true)
        hideError()

        lifecycleScope.launch {
            try {
                val response = ApiClient.service.login(LoginRequest(username, password))
                if (response.isSuccessful && response.body()?.success == true) {
                    val data = response.body()!!.data!!
                    PrefsManager.saveSession(this@LoginActivity, data.token, data.username)
                    startMain()
                } else {
                    val msg = response.body()?.message ?: getString(R.string.error_login)
                    showError(msg)
                }
            } catch (e: Exception) {
                showError(getString(R.string.error_network))
            } finally {
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
