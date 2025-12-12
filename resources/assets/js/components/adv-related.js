import Sortable from 'sortablejs'

// ====================================
// Utility: Debounce function
// ====================================
function debounce(func, wait) {
    let timeout
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout)
            func(...args)
        }
        clearTimeout(timeout)
        timeout = setTimeout(later, wait)
    }
}

// ====================================
// Collect related items into JSON
// ====================================
function collectRelatedItemsAndMakeJSON(elList) {
    const fieldInput = document.getElementById(elList.dataset.field)
    const data = []

    elList.querySelectorAll('.adv-related-item').forEach(elRow => {
        data.push(JSON.parse(elRow.dataset.data))
    })

    fieldInput.value = JSON.stringify(data)
}

// ====================================
// Check for duplicate items
// ====================================
function checkRelated(relatedObj, fieldName) {
    let result = false
    const list = document.getElementById('adv-related-list-' + fieldName)

    if (!list) return result

    list.querySelectorAll('.adv-related-item').forEach(item => {
        const title = item.querySelector('.adv-related-item__title').textContent
        if (title === relatedObj.display) {
            result = true
            return false
        }
    })

    return result
}

// ====================================
// Related item template
// ====================================
function relatedTemplate(relatedObj) {
    return `
        <div class="adv-related-item" data-data='${JSON.stringify(relatedObj.data)}'>
            <div class="adv-related-item__handle"><span></span><span></span><span></span></div>
            <div class="adv-related-item__title">${relatedObj.display}</div>
            <div class="adv-related-item__remove">
                <button data-field="${relatedObj.field}" type="button" class="btn btn-danger remove-related"><i class='voyager-x'></i></button>
            </div>
        </div>`
}

// ====================================
// Show autocomplete suggestions
// ====================================
function showSuggestions(input, suggestions) {
    // Remove old dropdown
    const oldDropdown = input.parentElement.querySelector('.adv-autocomplete-dropdown')
    if (oldDropdown) oldDropdown.remove()

    if (!suggestions || suggestions.length === 0) {
        return
    }

    const dropdown = document.createElement('ul')
    dropdown.className = 'adv-autocomplete-dropdown'

    suggestions.forEach(suggestion => {
        const li = document.createElement('li')
        li.className = 'adv-autocomplete-item'
        li.textContent = suggestion.value
        li.dataset.value = JSON.stringify(suggestion)

        li.addEventListener('click', e => {
            e.preventDefault()
            const data = JSON.parse(li.dataset.value)

            input.dataset.display = data.value
            input.dataset.data = JSON.stringify(data.data)
            input.value = ''

            dropdown.remove()
            input.parentElement.querySelector('.add-related').disabled = false
        })

        dropdown.appendChild(li)
    })

    input.parentElement.appendChild(dropdown)
}

// ====================================
// Handle autocomplete input
// ====================================
async function handleAutocomplete(event) {
    const input = event.target
    const query = input.value

    if (query.length < 1) {
        const oldDropdown = input.parentElement.querySelector('.adv-autocomplete-dropdown')
        if (oldDropdown) oldDropdown.remove()
        return
    }

    const url = input.dataset.url
    const params = new URLSearchParams({
        query: query,
        slug: input.dataset.slug,
        search_field: input.dataset.searchField,
        display_field: input.dataset.displayField,
        fields: input.dataset.fields
    })

    try {
        const response = await fetch(`${url}?${params}`)
        const data = await response.json()

        if (data.status === 'success' && data.suggestions) {
            showSuggestions(input, data.suggestions)
        } else {
            const oldDropdown = input.parentElement.querySelector('.adv-autocomplete-dropdown')
            if (oldDropdown) oldDropdown.remove()
        }
    } catch (error) {
        console.error('Autocomplete error:', error)
    }
}

// ====================================
// Initialize on DOM load
// ====================================
document.addEventListener('DOMContentLoaded', () => {
    // Initialize Sortable for all related lists
    document.querySelectorAll('.adv-related-list').forEach(list => {
        Sortable.create(list, {
            animation: 200,
            handle: '.adv-related-item__handle',
            onEnd: () => {
                collectRelatedItemsAndMakeJSON(list)
            }
        })
    })

    // Initialize autocomplete inputs
    document.querySelectorAll('.related-autocomplete').forEach(input => {
        input.addEventListener('input', debounce(handleAutocomplete, 300))
    })

    // Handle add button click
    document.addEventListener('click', e => {
        if (e.target.closest('.add-related')) {
            const button = e.target.closest('.add-related')
            const fieldName = button.dataset.field
            const input = document.getElementById('adv-related-autocomplete-' + fieldName)
            const list = document.getElementById('adv-related-list-' + fieldName)

            const relatedObj = {
                field: fieldName,
                display: input.dataset.display,
                display_field: input.dataset.displayField,
                data: JSON.parse(input.dataset.data)
            }

            if (!checkRelated(relatedObj, fieldName)) {
                list.insertAdjacentHTML('beforeend', relatedTemplate(relatedObj))
                collectRelatedItemsAndMakeJSON(list)
                input.value = ''
                input.dataset.display = ''
                input.dataset.data = ''
                button.disabled = true

                // Re-initialize Sortable after adding
                Sortable.get(list).destroy()
                Sortable.create(list, {
                    animation: 200,
                    handle: '.adv-related-item__handle',
                    onEnd: () => {
                        collectRelatedItemsAndMakeJSON(list)
                    }
                })
            }
        }
    })

    // Handle remove button click
    document.addEventListener('click', e => {
        if (e.target.closest('.remove-related')) {
            const button = e.target.closest('.remove-related')
            const fieldName = button.dataset.field
            const item = button.closest('.adv-related-item')
            const list = document.getElementById('adv-related-list-' + fieldName)

            item.remove()
            collectRelatedItemsAndMakeJSON(list)
        }
    })

    // Close dropdown when clicking outside
    document.addEventListener('click', e => {
        if (!e.target.closest('.adv-related-add-autocomplete')) {
            document.querySelectorAll('.adv-autocomplete-dropdown').forEach(dropdown => {
                dropdown.remove()
            })
        }
    })
})
