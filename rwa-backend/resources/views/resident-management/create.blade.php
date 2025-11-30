@extends('layouts.app')

@section('title', 'Add New Resident')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="/residents">All Residents</a>
                        </li>
                        <li class="breadcrumb-item active">Add New Resident</li>
                    </ol>
                </nav>
                <h1 class="page-title h3 mb-0">
                    <i class="fas fa-user-plus me-2"></i>Add New Resident
                </h1>
            </div>
            <div class="page-actions">
                <a href="/residents" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Residents
                </a>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>Resident Information
                    </h5>
                </div>
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="/residents" method="POST">
                        @csrf
                        
                        <!-- Basic Property Information -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="house_number" class="form-label">
                                        House Number <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control @error('house_number') is-invalid @enderror" 
                                           id="house_number" name="house_number" value="{{ old('house_number') }}" 
                                           placeholder="e.g., 101, A-202" required>
                                    @error('house_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="property_type" class="form-label">
                                        Property Type <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select @error('property_type') is-invalid @enderror" 
                                            id="property_type" name="property_type" required>
                                        <option value="">Select Property Type</option>
                                        <option value="house" {{ old('property_type') === 'house' ? 'selected' : '' }}>House</option>
                                        <option value="3bhk_flat" {{ old('property_type') === '3bhk_flat' ? 'selected' : '' }}>3 BHK Flat</option>
                                        <option value="villa" {{ old('property_type') === 'villa' ? 'selected' : '' }}>Villa</option>
                                        <option value="2bhk_flat" {{ old('property_type') === '2bhk_flat' ? 'selected' : '' }}>2 BHK Flat</option>
                                        <option value="1bhk_flat" {{ old('property_type') === '1bhk_flat' ? 'selected' : '' }}>1 BHK Flat</option>
                                        <option value="estonia_1" {{ old('property_type') === 'estonia_1' ? 'selected' : '' }}>Estonia 1</option>
                                        <option value="estonia_2" {{ old('property_type') === 'estonia_2' ? 'selected' : '' }}>Estonia 2</option>
                                        <option value="plot" {{ old('property_type') === 'plot' ? 'selected' : '' }}>Plot</option>
                                    </select>
                                    @error('property_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row" id="floor-row" style="display: {{ in_array(old('property_type'), ['3bhk_flat', '2bhk_flat', '1bhk_flat', 'estonia_1', 'estonia_2']) ? 'block' : 'none' }};">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="floor" class="form-label">Floor</label>
                                    <select class="form-select @error('floor') is-invalid @enderror" 
                                            id="floor" name="floor">
                                        <option value="">Select Floor</option>
                                        <option value="ground_floor" {{ old('floor') === 'ground_floor' ? 'selected' : '' }}>Ground Floor</option>
                                        <option value="1st_floor" {{ old('floor') === '1st_floor' ? 'selected' : '' }}>1st Floor</option>
                                        <option value="2nd_floor" {{ old('floor') === '2nd_floor' ? 'selected' : '' }}>2nd Floor</option>
                                    </select>
                                    @error('floor')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Owner Information -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="owner_name" class="form-label">
                                        Owner Name <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control @error('owner_name') is-invalid @enderror" 
                                           id="owner_name" name="owner_name" value="{{ old('owner_name') }}" 
                                           placeholder="Enter owner name" required>
                                    @error('owner_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="contact_number" class="form-label">
                                        Contact Number <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control @error('contact_number') is-invalid @enderror" 
                                           id="contact_number" name="contact_number" value="{{ old('contact_number') }}" 
                                           placeholder="e.g., +91 9876543210" required>
                                    @error('contact_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                           id="email" name="email" value="{{ old('email') }}" 
                                           placeholder="e.g., resident@example.com">
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="address" class="form-label">Address</label>
                                    <textarea class="form-control @error('address') is-invalid @enderror" 
                                              id="address" name="address" rows="3" 
                                              placeholder="Complete address">{{ old('address') }}</textarea>
                                    @error('address')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Status Information -->
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="status" class="form-label">
                                        Status <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select @error('status') is-invalid @enderror" 
                                            id="status" name="status" required>
                                        <option value="">Select Status</option>
                                        <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Active</option>
                                        <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                        <option value="pending" {{ old('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="current_state" class="form-label">Current State</label>
                                    <select class="form-select @error('current_state') is-invalid @enderror" 
                                            id="current_state" name="current_state">
                                        <option value="">Select Current State</option>
                                        <option value="occupied" {{ old('current_state') === 'occupied' ? 'selected' : '' }}>Occupied</option>
                                        <option value="vacant" {{ old('current_state') === 'vacant' ? 'selected' : '' }}>Vacant</option>
                                    </select>
                                    @error('current_state')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="monthly_maintenance" class="form-label">Monthly Maintenance (₹)</label>
                                    <input type="number" class="form-control @error('monthly_maintenance') is-invalid @enderror" 
                                           id="monthly_maintenance" name="monthly_maintenance" value="{{ old('monthly_maintenance') }}" 
                                           min="0" step="0.01" placeholder="e.g., 5000">
                                    @error('monthly_maintenance')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Additional Information -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="move_in_date" class="form-label">Move-in Date</label>
                                    <input type="date" class="form-control @error('move_in_date') is-invalid @enderror" 
                                           id="move_in_date" name="move_in_date" value="{{ old('move_in_date') }}">
                                    @error('move_in_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="emergency_contact" class="form-label">Emergency Contact</label>
                                    <input type="text" class="form-control @error('emergency_contact') is-invalid @enderror" 
                                           id="emergency_contact" name="emergency_contact" value="{{ old('emergency_contact') }}" 
                                           placeholder="Emergency contact person name">
                                    @error('emergency_contact')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="emergency_phone" class="form-label">Emergency Phone</label>
                                    <input type="text" class="form-control @error('emergency_phone') is-invalid @enderror" 
                                           id="emergency_phone" name="emergency_phone" value="{{ old('emergency_phone') }}" 
                                           placeholder="e.g., +91 9876543210">
                                    @error('emergency_phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="remarks" class="form-label">Remarks</label>
                                    <textarea class="form-control @error('remarks') is-invalid @enderror" 
                                              id="remarks" name="remarks" rows="3" 
                                              placeholder="Any additional notes or remarks">{{ old('remarks') }}</textarea>
                                    @error('remarks')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="/residents" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Create Resident
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const propertyTypeSelect = document.getElementById('property_type');
    const floorRow = document.getElementById('floor-row');
    const floorInput = document.getElementById('floor');
    
    // Property types that require floor input
    const flatTypes = ['3bhk_flat', '2bhk_flat', '1bhk_flat', 'estonia_1', 'estonia_2'];
    
    function toggleFloorField() {
        const selectedType = propertyTypeSelect.value;
        
        if (flatTypes.includes(selectedType)) {
            floorRow.style.display = 'block';
        } else {
            floorRow.style.display = 'none';
            floorInput.value = ''; // Clear floor value when hidden
        }
    }
    
    // Check on page load (for old input values)
    toggleFloorField();
    
    // Check when property type changes
    propertyTypeSelect.addEventListener('change', toggleFloorField);
});
</script>

@endsection