package com.company.safedocshare.adapters

import android.view.LayoutInflater
import android.view.ViewGroup
import androidx.recyclerview.widget.DiffUtil
import androidx.recyclerview.widget.ListAdapter
import androidx.recyclerview.widget.RecyclerView
import com.company.safedocshare.databinding.ItemPdfBinding
import com.company.safedocshare.models.PdfFile

class PdfAdapter(
    private val onClick: (PdfFile) -> Unit
) : ListAdapter<PdfFile, PdfAdapter.PdfViewHolder>(DIFF) {

    inner class PdfViewHolder(private val binding: ItemPdfBinding) :
        RecyclerView.ViewHolder(binding.root) {

        fun bind(pdf: PdfFile) {
            binding.tvName.text = pdf.name
            binding.tvMeta.text = "${pdf.formattedSize()}  •  ${pdf.uploadedAt.take(10)}"
            binding.root.setOnClickListener { onClick(pdf) }
        }
    }

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): PdfViewHolder {
        val binding = ItemPdfBinding.inflate(LayoutInflater.from(parent.context), parent, false)
        return PdfViewHolder(binding)
    }

    override fun onBindViewHolder(holder: PdfViewHolder, position: Int) {
        holder.bind(getItem(position))
    }

    companion object {
        private val DIFF = object : DiffUtil.ItemCallback<PdfFile>() {
            override fun areItemsTheSame(a: PdfFile, b: PdfFile) = a.id == b.id
            override fun areContentsTheSame(a: PdfFile, b: PdfFile) = a == b
        }
    }
}
