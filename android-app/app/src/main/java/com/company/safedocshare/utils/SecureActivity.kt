package com.company.safedocshare.utils

import android.os.Bundle
import android.view.WindowManager
import androidx.appcompat.app.AppCompatActivity

/**
 * Base activity that prevents screenshots and screen recording via FLAG_SECURE.
 * All activities in the app extend this class.
 */
abstract class SecureActivity : AppCompatActivity() {

    override fun onCreate(savedInstanceState: Bundle?) {
        // Block screenshots and screen recording at OS level
        window.setFlags(
            WindowManager.LayoutParams.FLAG_SECURE,
            WindowManager.LayoutParams.FLAG_SECURE
        )
        super.onCreate(savedInstanceState)
    }
}
