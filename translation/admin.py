# Register your models here.
from django.contrib import admin
from .models import TranslationHistory

@admin.register(TranslationHistory)
class TranslationHistoryAdmin(admin.ModelAdmin):
    list_display = ("id", "source_text", "translated_text", "target_lang", "created_at")
    search_fields = ("source_text", "translated_text")
