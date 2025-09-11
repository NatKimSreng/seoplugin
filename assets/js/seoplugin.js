jQuery(document).ready(function ($) {
    let imageSelected = false;
    $('#seoplugin_og_image_button').on('click', function (e) {
    e.preventDefault();

    const $button = $(this);
    $button.prop('disabled', true).text('Processing...');

    const image_frame = wp.media({
        title: 'Select OG Image',
        library: { type: 'image' },
        button: { text: 'Use This Image' },
        multiple: false
    });

    image_frame.on('select', function () {
        imageSelected = true;
        const attachment = image_frame.state().get('selection').first().toJSON();
        const imageId = attachment.id;
        $.post(seoplugin_ajax.ajax_url, {
            action: 'seoplugin_regen_og_custom',
            attachment_id: imageId
        }, function (res) {
            $button.prop('disabled', false).text('Select OG Image');
            if (res.success) {
                $('#seoplugin_og_image_id').val(imageId);
                $('#seoplugin_og_image_preview').html(
                    '<img src="' + res.data.url + '" style="width:527px; height:352px; object-fit:cover;" />'
                );
            } else {
                alert('Error: ' + res.data);
            }
        }).fail(function () {
            $button.prop('disabled', false).text('Select OG Image');
            alert('AJAX request failed. Please try again.');
        });
    });

    image_frame.on('close', function () {
        if (!imageSelected) {
            // User canceled without selecting an image
            $button.prop('disabled', false).text('Select OG Image');
        }
        // else: do nothing here because button will be enabled after AJAX finishes
    });
    image_frame.open();
    });

    initCharCounter('seoplugin_meta_title', 65, 'seoTitleCharCount');
    initCharCounter('seoplugin_meta_description', 160, 'seoDescriptionCharCount');
    
    // Initialize SEO Analysis
    initSEOAnalysis();
    
    // Initialize Social Previews
    initSocialPreviews();
    
    // Initialize Focus Keyword Analysis
    initFocusKeywordAnalysis();
    
    // Initialize AI Assistant
    initAIAssistant();
});

function initCharCounter(textboxID, maxLength, charCountID) {
  const $textbox = jQuery('#' + textboxID);
  const $counter = jQuery('#' + charCountID);

  const initCount = $textbox.val().length;
  $counter.text(initCount);

  $textbox.attr('maxlength', maxLength);

  $textbox.on('input', function () {
    let text = jQuery(this).val();
    let count = text.length;

    if (count > maxLength) {
      jQuery(this).val(text.substring(0, maxLength));
      count = maxLength;
    }

    $counter.text(count);
    
    // Update SEO analysis when title or description changes
    if (textboxID === 'seoplugin_meta_title' || textboxID === 'seoplugin_meta_description') {
      updateSEOAnalysis();
    }
  });
}

function initSEOAnalysis() {
    updateSEOAnalysis();
    
    // Update analysis when any field changes
    jQuery('#seoplugin_meta_title, #seoplugin_meta_description, #seoplugin_og_image_id, #seoplugin_focus_keyword').on('input change', function() {
        updateSEOAnalysis();
    });
}

function updateSEOAnalysis() {
    const title = jQuery('#seoplugin_meta_title').val();
    const description = jQuery('#seoplugin_meta_description').val();
    const ogImage = jQuery('#seoplugin_og_image_id').val();
    const focusKeyword = jQuery('#seoplugin_focus_keyword').val();
    
    let score = 0;
    let maxScore = 100;
    
    // Title analysis
    const titleLength = title.length;
    let titleStatus = '❌';
    if (titleLength >= 30 && titleLength <= 65) {
        titleStatus = '✅';
        score += 25;
    } else if (titleLength > 0 && titleLength < 30) {
        titleStatus = '⚠️';
        score += 15;
    } else if (titleLength > 65) {
        titleStatus = '⚠️';
        score += 10;
    }
    jQuery('#title-status').text(titleStatus);
    
    // Description analysis
    const descLength = description.length;
    let descStatus = '❌';
    if (descLength >= 120 && descLength <= 160) {
        descStatus = '✅';
        score += 25;
    } else if (descLength > 0 && descLength < 120) {
        descStatus = '⚠️';
        score += 15;
    } else if (descLength > 160) {
        descStatus = '⚠️';
        score += 10;
    }
    jQuery('#desc-status').text(descStatus);
    
    // Image analysis
    let imageStatus = '❌';
    if (ogImage && ogImage !== '') {
        imageStatus = '✅';
        score += 25;
    }
    jQuery('#image-status').text(imageStatus);
    
    // Keyword analysis
    let keywordStatus = '❌';
    if (focusKeyword && focusKeyword !== '') {
        const keywordInTitle = title.toLowerCase().includes(focusKeyword.toLowerCase());
        const keywordInDesc = description.toLowerCase().includes(focusKeyword.toLowerCase());
        
        if (keywordInTitle && keywordInDesc) {
            keywordStatus = '✅';
            score += 25;
        } else if (keywordInTitle || keywordInDesc) {
            keywordStatus = '⚠️';
            score += 15;
        } else {
            keywordStatus = '⚠️';
            score += 10;
        }
    }
    jQuery('#keyword-status').text(keywordStatus);
    
    // Update score
    jQuery('#seo-score').text(score);
    
    // Update score circle color
    const scoreCircle = jQuery('.seo-score-circle');
    scoreCircle.removeClass('score-excellent score-good score-fair score-poor');
    
    if (score >= 80) {
        scoreCircle.addClass('score-excellent');
    } else if (score >= 60) {
        scoreCircle.addClass('score-good');
    } else if (score >= 40) {
        scoreCircle.addClass('score-fair');
    } else {
        scoreCircle.addClass('score-poor');
    }
}

function initSocialPreviews() {
    // Tab switching
    jQuery('.social-tab').on('click', function() {
        const tab = jQuery(this).data('tab');
        
        // Update active tab
        jQuery('.social-tab').removeClass('active');
        jQuery(this).addClass('active');
        
        // Update active preview
        jQuery('.social-preview').removeClass('active');
        jQuery('#' + tab + '-preview').addClass('active');
    });
    
    // Update previews when content changes
    jQuery('#seoplugin_meta_title, #seoplugin_meta_description, #seoplugin_og_image_id').on('input change', function() {
        updateSocialPreviews();
    });
}

function updateSocialPreviews() {
    const title = jQuery('#seoplugin_meta_title').val() || 'Your Title Here';
    const description = jQuery('#seoplugin_meta_description').val() || 'Your description here...';
    const ogImage = jQuery('#seoplugin_og_image_id').val();
    
    // Update all previews
    jQuery('.social-title, .twitter-title').text(title);
    jQuery('.social-description, .twitter-description').text(description);
    
    if (ogImage && ogImage !== '') {
        // Image will be updated when selected
    } else {
        jQuery('.social-image img, .twitter-image img').hide();
        jQuery('.no-image').show();
    }
}

function initFocusKeywordAnalysis() {
    jQuery('#seoplugin_focus_keyword').on('input', function() {
        const keyword = jQuery(this).val();
        if (keyword) {
            // Check if keyword appears in title and description
            const title = jQuery('#seoplugin_meta_title').val();
            const description = jQuery('#seoplugin_meta_description').val();
            
            const titleContains = title.toLowerCase().includes(keyword.toLowerCase());
            const descContains = description.toLowerCase().includes(keyword.toLowerCase());
            
            // Add visual indicators
            if (titleContains) {
                jQuery('#seoplugin_meta_title').addClass('keyword-found');
            } else {
                jQuery('#seoplugin_meta_title').removeClass('keyword-found');
            }
            
            if (descContains) {
                jQuery('#seoplugin_meta_description').addClass('keyword-found');
            } else {
                jQuery('#seoplugin_meta_description').removeClass('keyword-found');
            }
        } else {
            jQuery('#seoplugin_meta_title, #seoplugin_meta_description').removeClass('keyword-found');
        }
    });
}

function initAIAssistant() {
    // AI Title Generation
    jQuery('#ai-generate-title').on('click', function() {
        const postId = jQuery('#post_ID').val();
        if (!postId) {
            alert('Please save the post first before generating AI suggestions.');
            return;
        }
        
        showAILoading();
        jQuery.post(seoplugin_ajax.ajax_url, {
            action: 'seoplugin_ai_generate_title',
            post_id: postId
        }, function(response) {
            hideAILoading();
            if (response.success) {
                showAISuggestions('Title Suggestions', response.data.titles, 'title');
            } else {
                alert('Error: ' + response.data);
            }
        }).fail(function() {
            hideAILoading();
            alert('AI request failed. Please try again.');
        });
    });

    // AI Description Generation
    jQuery('#ai-generate-description').on('click', function() {
        const postId = jQuery('#post_ID').val();
        if (!postId) {
            alert('Please save the post first before generating AI suggestions.');
            return;
        }
        
        showAILoading();
        jQuery.post(seoplugin_ajax.ajax_url, {
            action: 'seoplugin_ai_generate_description',
            post_id: postId
        }, function(response) {
            hideAILoading();
            if (response.success) {
                showAISuggestions('Description Suggestions', response.data.descriptions, 'description');
            } else {
                alert('Error: ' + response.data);
            }
        }).fail(function() {
            hideAILoading();
            alert('AI request failed. Please try again.');
        });
    });

    // AI Keyword Suggestions
    jQuery('#ai-suggest-keywords').on('click', function() {
        const postId = jQuery('#post_ID').val();
        if (!postId) {
            alert('Please save the post first before generating AI suggestions.');
            return;
        }
        
        showAILoading();
        jQuery.post(seoplugin_ajax.ajax_url, {
            action: 'seoplugin_ai_suggest_keywords',
            post_id: postId
        }, function(response) {
            hideAILoading();
            if (response.success) {
                showAISuggestions('Keyword Suggestions', response.data.keywords, 'keywords');
            } else {
                alert('Error: ' + response.data);
            }
        }).fail(function() {
            hideAILoading();
            alert('AI request failed. Please try again.');
        });
    });

    // AI Content Analysis
    jQuery('#ai-analyze-content').on('click', function() {
        const postId = jQuery('#post_ID').val();
        if (!postId) {
            alert('Please save the post first before generating AI suggestions.');
            return;
        }
        
        showAILoading();
        jQuery.post(seoplugin_ajax.ajax_url, {
            action: 'seoplugin_ai_analyze_content',
            post_id: postId
        }, function(response) {
            hideAILoading();
            if (response.success) {
                showAISuggestions('SEO Analysis', response.data.analysis, 'analysis');
            } else {
                alert('Error: ' + response.data);
            }
        }).fail(function() {
            hideAILoading();
            alert('AI request failed. Please try again.');
        });
    });
}

function showAILoading() {
    jQuery('#ai-loading').show();
    jQuery('#ai-suggestions').hide();
    jQuery('.ai-controls button').prop('disabled', true);
}

function hideAILoading() {
    jQuery('#ai-loading').hide();
    jQuery('.ai-controls button').prop('disabled', false);
}

function showAISuggestions(title, content, type) {
    let formattedContent = '';
    
    if (type === 'title' || type === 'description') {
        const suggestions = content.split('\n').filter(line => line.trim());
        formattedContent = '<div class="ai-suggestion-list">';
        suggestions.forEach((suggestion, index) => {
            const cleanSuggestion = suggestion.replace(/^\d+\.\s*/, '').trim();
            if (cleanSuggestion) {
                formattedContent += `
                    <div class="ai-suggestion-item">
                        <div class="suggestion-text">${cleanSuggestion}</div>
                        <button class="button button-small use-suggestion" data-type="${type}" data-text="${cleanSuggestion.replace(/"/g, '&quot;')}">Use This</button>
                    </div>
                `;
            }
        });
        formattedContent += '</div>';
    } else if (type === 'keywords') {
        const keywords = content.split(',').map(k => k.trim()).filter(k => k);
        formattedContent = '<div class="ai-keyword-list">';
        keywords.forEach(keyword => {
            formattedContent += `<span class="keyword-tag">${keyword}</span>`;
        });
        formattedContent += '</div>';
    } else if (type === 'analysis') {
        formattedContent = `<div class="ai-analysis-content">${content.replace(/\n/g, '<br>')}</div>`;
    }
    
    jQuery('#ai-suggestion-content').html(formattedContent);
    jQuery('#ai-suggestions').show();
    
    // Handle suggestion usage
    jQuery('.use-suggestion').on('click', function() {
        const suggestionType = jQuery(this).data('type');
        const suggestionText = jQuery(this).data('text');
        
        if (suggestionType === 'title') {
            jQuery('#seoplugin_meta_title').val(suggestionText);
            updateSEOAnalysis();
        } else if (suggestionType === 'description') {
            jQuery('#seoplugin_meta_description').val(suggestionText);
            updateSEOAnalysis();
        }
        
        jQuery(this).text('Used!').addClass('used');
    });
}