
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true" >

        <div class="modal-dialog modal-lg modal-dialog-centered">

            <div class="modal-content">

                <form
                    action="{{ route('admin.users.update', $user->id) }}"
                    method="POST"
                >

                    @csrf
                    @method('PUT')

                    <div class="modal-header">

                        <h5 class="modal-title">
                            <i class="fas fa-user-edit me-1"></i>
                            Edit User Profile
                        </h5>

                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                        ></button>

                    </div>


                    <div class="modal-body">

                        <div class="row g-3">

                            {{-- Name --}}
                            <div class="col-md-6">

                                <label class="form-label">
                                    Name
                                </label>

                                <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}">

                            </div>


                            {{-- Email --}}
                            <div class="col-md-6">

                                <label class="form-label">
                                    Email
                                </label>

                                <input  type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" >

                            </div>


                            {{-- Wallet --}}
                            <div class="col-12">
                                <label class="form-label">
                                    Wallet Address
                                </label>

                                <input type="text" name="wallet_address" class="form-control" value="{{ old('wallet_address', $user->wallet_address) }}" >

                            </div>
                            {{-- Address --}}
                            <div class="col-12">

                                <label class="form-label">
                                    Address
                                </label>

                                <textarea  name="address" class="form-control" rows="3" >{{ old('address', $user->address) }}</textarea>

                            </div>

                        </div>

                    </div>


                    <div class="modal-footer">

                        <button
                            type="button"
                            class="btn btn-secondary"
                            data-bs-dismiss="modal"
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            <i class="fas fa-save me-1"></i>
                            Save Changes
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>
