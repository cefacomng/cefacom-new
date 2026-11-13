import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { Checkbox, Input, Select, Textarea } from './Field';

describe('form fields', () => {
    it('associates the label with the input', () => {
        render(<Input label="Email address" />);
        expect(screen.getByLabelText('Email address')).toBeInTheDocument();
    });

    it('links the hint through aria-describedby', () => {
        render(<Input label="Name" hint="Only you can see this." />);
        const input = screen.getByLabelText('Name');
        expect(input).toHaveAccessibleDescription('Only you can see this.');
        expect(input).not.toHaveAttribute('aria-invalid');
    });

    it('marks the input invalid and announces the error instead of the hint', () => {
        render(<Input label="Phone" hint="Digits only" error="Enter a valid phone number." />);
        const input = screen.getByLabelText('Phone');
        expect(input).toHaveAttribute('aria-invalid', 'true');
        expect(input).toHaveAccessibleDescription('Enter a valid phone number.');
        expect(screen.getByRole('alert')).toHaveTextContent('Enter a valid phone number.');
        expect(screen.queryByText('Digits only')).not.toBeInTheDocument();
    });

    it('supports textarea, select and checkbox labels', () => {
        render(
            <>
                <Textarea label="Message" />
                <Select label="Channel" options={[{ value: 'sms', label: 'SMS' }]} />
                <Checkbox label="I have consent" />
            </>,
        );
        expect(screen.getByLabelText('Message')).toBeInstanceOf(HTMLTextAreaElement);
        expect(screen.getByLabelText('Channel')).toBeInstanceOf(HTMLSelectElement);
        expect(screen.getByLabelText('I have consent')).toHaveAttribute('type', 'checkbox');
    });
});
